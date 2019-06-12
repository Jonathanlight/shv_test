<?php

namespace App\Controller\Api;

use App\Entity\Hedge;
use App\Entity\HedgeLog;
use App\Entity\Interfaces\CommentInterface;
use App\Entity\RMP;
use App\Entity\RMPLog;
use App\Entity\RmpSubSegment;
use App\Entity\User;
use App\Form\CommentType;
use App\Form\DataTransformer\XssTransformer;
use App\Service\CommentManager;
use App\Service\LogManager;
use App\Service\NotificationManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorInterface;


class CommentController extends AbstractController
{

    /**
     * @Route(path="/api/comment/{id}/{type}", name="api_comment_remove", methods={"GET"})
     *
     * @param int $id
     * @param string $type
     *
     * @return JsonResponse
     */
    public function removeAction(int $id, string $type)
    {
        $comment = $this->getDoctrine()->getRepository('App:'.$type)->find($id);
        $this->denyAccessUnlessGranted('comment_delete', $comment);

        if ($comment instanceof CommentInterface) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($comment);
            $em->flush();

        }

        return new JsonResponse(['commentId' => $comment->getId()]);
    }

    /**
     * @Route(path="/api/comment/{id}/{type}", name="api_comment_edit", methods={"POST"})
     *
     * @param Request $request
     * @param int $id
     * @param string $type
     *
     * @return JsonResponse
     */
    public function editAction(Request $request, int $id, string $type)
    {
        $comment = $this->getDoctrine()->getRepository('App:'.$type)->find($id);
        $this->denyAccessUnlessGranted('comment_edit', $comment);
        $message = $request->request->get('message');
        if (($comment instanceof CommentInterface) && !empty($message)) {
            $em = $this->getDoctrine()->getManager();
            $comment->setMessage($message);
            $comment->setUpdatedAt(new \DateTime());
            $em->persist($comment);
            $em->flush();
        }

        return new JsonResponse(['commentId' => $comment->getId(), 'message' => $comment->getMessage(),
                                 'updateDate' => $comment->getUpdatedAt()->format('m/d/Y'),
                                 'updateTime' => $comment->getUpdatedAt()->format('H:i a')]);
    }

    /**
     * @Route(path="/api/comment/{parentId}/{parentClass}/add", name="api_comment_add", methods={"POST"})
     *
     * @param Request $request
     * @param int $parentId
     * @param string $parentClass
     * @param CommentManager $commentManager
     * @param NotificationManager $notificationManager
     * @param LogManager $logManager
     * @param TranslatorInterface $translator
     * @param XssTransformer $xssTransformer
     *
     * @return Response
     */
    public function addAction(Request $request, int $parentId, string $parentClass, CommentManager $commentManager,
                              NotificationManager $notificationManager, LogManager $logManager, TranslatorInterface $translator, XssTransformer $xssTransformer)
    {
        $message = $xssTransformer->reverseTransform(trim($request->request->get('message')));
        $parent = $this->getDoctrine()->getRepository('App:'.$parentClass)->find($parentId);
        $user = $this->getUser();
        $comment = $commentManager->createComment($parent, $message, $user);
        $userRepository = $this->getDoctrine()->getRepository(User::class);

        if ($parent instanceof Hedge && !$parent->isDraft()) {
            $type = NotificationManager::TYPE_HEDGE_COMMENT;
            $entity = $parent;
            $businessUnit = $entity->getRmp()->getBusinessUnit();

            $recipients = $userRepository->findByRolesAndBusinessUnit([User::ROLE_BU_HEDGING_COMMITTEE],
                                                                        $businessUnit,
                                                                        $user);

            if ($parent->isPendingExecution()) {
                $traders = $userRepository->findByRole(User::ROLE_TRADER, $user);
                $recipients = array_merge($recipients, $traders);
            } else if ($parent->isPendingApproval()) {
                $recipients = array_merge($recipients, $userRepository->findByRole(User::ROLE_RISK_CONTROLLER, $user));
                $recipients = array_merge($recipients, $userRepository->findByRolesAndBusinessUnit([User::ROLE_BOARD_MEMBER], $businessUnit, $user));
            }

            if ($entity->getCreator()->getId() != $user->getId() && !in_array($entity->getCreator(), $recipients)) {
                $recipients[] = $entity->getCreator();
            }

            $bindings = [
                'hedgeId' => $entity->getId(),
                'hedgeStatus' => $translator->trans(Hedge::$statusLabelsAll[$entity->getStatus()]),
                'userName' => $user->getFirstName() . ' ' . $user->getLastName(),
                'url' => $this->generateUrl('hedge_edit', ['hedge' => $entity->getId()], UrlGeneratorInterface::ABSOLUTE_URL)
            ];
            $logType = HedgeLog::TYPE_COMMENT;
            $additionalMessage = $translator->trans('alerts.hedge.comment_additional', ['%userName%' => $user->getFirstName() . ' ' . $user->getLastName()]);

        } else if ($parent instanceof RmpSubSegment) {
            $type = NotificationManager::TYPE_RMP_COMMENT;
            $entity = $parent->getRmp();
            $riskControllers = $userRepository->findByRole(User::ROLE_RISK_CONTROLLER);
            $recipients = $userRepository->findByRolesAndBusinessUnit([User::ROLE_BOARD_MEMBER, User::ROLE_BU_HEDGING_COMMITTEE],
                                                                      $entity->getBusinessUnit(),
                                                                      $user);

            $recipients = array_merge($riskControllers, $recipients);
            $bindings = [
              'rmpName' => $entity->getName(),
              'subSegment' => $parent->getSubSegment()->getName(),
              'userName' => $user->getFirstName() . ' ' . $user->getLastName(),
              'url' => $this->generateUrl('rmp_view', ['rmp' => $entity->getId()],UrlGeneratorInterface::ABSOLUTE_URL)
            ];
            $logType = RMPLog::TYPE_COMMENT;
            $additionalMessage = $translator->trans('alerts.rmp.comment_additional', ['%userName%' => $user->getFirstName() . ' ' . $user->getLastName(),
                                                                                          '%subSegment%' => $parent->getSubSegment()->getName()]);
        }

        if (isset($type) && isset($entity) && isset($bindings) && isset($recipients) && isset($logType) && isset($additionalMessage)) {
            $notificationManager->sendNotification($type, $entity, $recipients, $bindings, $additionalMessage);
            $logManager->createLog($entity,  $this->getUser(), $logType, $message);
        }


        return new JsonResponse(['id' => $comment->getId(), 'message' => $comment->getMessage(),
                                 'type' => $comment->getClassName(), 'date' => $comment->getTimestamp()->format('m/d/Y'),
                                 'time' => $comment->getTimestamp()->format('H:i a')]);
    }


    /**
     *
     * @param Request $request
     * @param Hedge|RmpSubSegment $parent
     * @param CommentManager $commentManager
     * @return RedirectResponse
     */
    public function commentAction(Request $request, $parent, CommentManager $commentManager): RedirectResponse
    {
        $formComment = $this->createForm(CommentType::class, null);
        $formComment->handleRequest($request);
        $formData = $formComment->getData();

        if ($parent instanceof Hedge) {
            $commentManager->createComment($parent, $formData['message'], $this->getUser());
            return $this->redirectToRoute('hedge_edit', ['hedge' => $parent->getId()]);
        } elseif ($parent instanceof RmpSubSegment) {
            $commentManager->createComment($parent, $formData['message'], $this->getUser());
            return $this->redirectToRoute('rmp_view', ['rmp' => $parent->getRmp()->getId()]);
        }

        return $this->redirectToRoute('homepage');
    }

    /**
     * @param RmpSubSegment|Hedge $parent
     * @param string $pathClass
     * @return JsonResponse
     */
    public function modalContentAction($parent, $pathClass): JsonResponse
    {
        $response = [];

        $classExploded = explode('\\', $pathClass);
        $class = $classExploded[count($classExploded)-1];

        if (class_exists($pathClass)) {
            $commentClass = $pathClass.'Comment';
            $comments = $this->getDoctrine()->getRepository($commentClass)->findBy(['parent' => $parent], ['updatedAt' => 'DESC']);
            $action = $this->generateUrl(lcfirst($class).'_comment_add', [lcfirst($class) => $parent->getId()]);

            $formComment = $this->createForm(CommentType::class, null, [
                'action' => $action
            ]);

            $response['content'] = $this->container->get('twig')->render('common/modal_comment_content.html.twig', [
                'parent' => $parent,
                'parentClass' => $class,
                'comments' => $comments,
                'formComment' => $formComment->createView()
            ]);

            if ($parent instanceof RmpSubSegment) {
                $response['subSegmentName'] = $parent->getSubSegment()->getName();
            }
        }

        return new JsonResponse($response);
    }
}
