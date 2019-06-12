<?php
namespace App\Controller\Admin;
use Lexik\Bundle\TranslationBundle\Form\Type\TransUnitType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
/**
 * Integrate LexikTranslationBundle into SonataAdmin.
 */
class TranslationController extends \Lexik\Bundle\TranslationBundle\Controller\TranslationController
{
    /**
     * Display the translation grid.
     *
     * @return Response
     */
    public function gridAction()
    {
        $tokens = null;
        if ($this->container->getParameter('lexik_translation.dev_tools.enable')) {
            $tokens = $this->get('lexik_translation.token_finder')->find();
        }

        $key = (!empty($domain) && !empty($page)) ? $domain . '.' . $page : '';

        return $this->render('translation/grid.html.twig', array(
            'layout'         => $this->container->getParameter('lexik_translation.base_layout'),
            'admin_pool' => $this->get('sonata.admin.pool'),
            'inputType'      => $this->container->getParameter('lexik_translation.grid_input_type'),
            'base_template' => $this->get('sonata.admin.pool')->getTemplate('layout'),
            'autoCacheClean' => $this->container->getParameter('lexik_translation.auto_cache_clean'),
            'toggleSimilar'  => $this->container->getParameter('lexik_translation.grid_toggle_similar'),
            'locales'        => $this->getManagedLocales(),
            'tokens'         => $tokens,
            'key' => $key
        ));
    }
}