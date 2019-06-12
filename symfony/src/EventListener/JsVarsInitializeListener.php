<?php

namespace App\EventListener;

use App\Service\JsVars;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

class JsVarsInitializeListener
{
    /**
     * @var JsVars
     */
    private $jsVars;

    /**
     * @var bool
     */
    private $appDebug;

    /**
     * @param JsVars $jsVars
     * @param bool   $appDebug
     */
    public function __construct(JsVars $jsVars, $appDebug)
    {
        $this->jsVars = $jsVars;
        $this->appDebug = $appDebug;
    }

    /**
     * Initialize js vars.
     *
     * @param FilterControllerEvent $event
     *
     * @throws \Exception
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        // JsVars service will only initialize for HTML request
        if ($event->getRequest()->isXmlHttpRequest()) {
            return;
        }

        // Simple variables
        $this->jsVars->debug = $this->appDebug;

        // Translations
        $this->jsVars->trans('modal.cancel.title');
        $this->jsVars->trans('modal.cancel.text');
        $this->jsVars->trans('modal.cancel.confirm_delete');
        $this->jsVars->trans('modal.cancel.confirm_cancel');
        $this->jsVars->trans('modal.cancel.success_delete');
        $this->jsVars->trans('modal.cancel.success_cancel');
        $this->jsVars->trans('modal.cancel.success_pending_cancelation');
        $this->jsVars->trans('modal.hedge.execution_request.title');
        $this->jsVars->trans('modal.hedge.execution_request.text_without_waivers');
        $this->jsVars->trans('modal.hedge.execution_request.text_with_waivers');
        $this->jsVars->trans('modal.hedge.execution_request.type_sell');
        $this->jsVars->trans('modal.hedge.execution_request.confirm_btn_with_waivers');
        $this->jsVars->trans('modal.hedge.execution_request.confirm_btn_without_waivers');
        $this->jsVars->trans('modal.hedge.execution_request.success_message');
        $this->jsVars->trans('modal.hedge.execution_request.error_blocked');
        $this->jsVars->trans('modal.hedge.execution_request.error_sell_volume');
        $this->jsVars->trans('modal.hedge.return.confirm_message');
        $this->jsVars->trans('modal.hedge.accept.title');
        $this->jsVars->trans('modal.hedge.accept.text');
        $this->jsVars->trans('modal.hedge.accept.confirm_btn');
        $this->jsVars->trans('modal.hedge.accept.confirm_message');
        $this->jsVars->trans('modal.hedge.refuse.confirm_btn');
        $this->jsVars->trans('modal.hedge.refuse.confirm_message');
        $this->jsVars->trans('modal.hedge.write_off.title');
        $this->jsVars->trans('modal.hedge.write_off.text');
        $this->jsVars->trans('modal.hedge.write_off.confirm_btn');
        $this->jsVars->trans('modal.hedge.write_off.confirm_message');
        $this->jsVars->trans('modal.comment.title');
        $this->jsVars->trans('modal.comment.text');
        $this->jsVars->trans('modal.comment.confirm_btn');
        $this->jsVars->trans('modal.comment.confirm_message');
        $this->jsVars->trans('modal.hedge.return.title');
        $this->jsVars->trans('modal.hedge.return.text');
        $this->jsVars->trans('modal.hedge.return.confirm_btn');
        $this->jsVars->trans('modal.rmp.sub_segment.remove.title');
        $this->jsVars->trans('modal.rmp.sub_segment.remove.text');
        $this->jsVars->trans('modal.rmp.sub_segment.remove.confirm_btn');
        $this->jsVars->trans('modal.rmp.sub_segment.remove.confirm_message');
        $this->jsVars->trans('modal.rmp.sub_segment.error');
        $this->jsVars->trans('modal.rmp.block.title');
        $this->jsVars->trans('modal.rmp.block.text');
        $this->jsVars->trans('modal.rmp.block.confirm_btn');
        $this->jsVars->trans('modal.rmp.block.confirm_message');
        $this->jsVars->trans('modal.rmp.unblock.title');
        $this->jsVars->trans('modal.rmp.unblock.text');
        $this->jsVars->trans('modal.rmp.unblock.confirm_btn');
        $this->jsVars->trans('modal.rmp.unblock.confirm_message');
        $this->jsVars->trans('modal.rmp.approval.send.title');
        $this->jsVars->trans('modal.rmp.approval.send.text');
        $this->jsVars->trans('modal.rmp.approval.send.confirm_btn');
        $this->jsVars->trans('modal.rmp.approval.send.confirm_message');
        $this->jsVars->trans('modal.rmp.approval.accept.title');
        $this->jsVars->trans('modal.rmp.approval.accept.text');
        $this->jsVars->trans('modal.rmp.approval.accept.confirm_btn');
        $this->jsVars->trans('modal.rmp.approval.accept.confirm_message');
        $this->jsVars->trans('modal.rmp.approval.refuse.title');
        $this->jsVars->trans('modal.rmp.approval.refuse.text');
        $this->jsVars->trans('modal.rmp.approval.refuse.confirm_btn');
        $this->jsVars->trans('modal.rmp.approval.refuse.confirm_message');
        $this->jsVars->trans('modal.rmp.cancel.title');
        $this->jsVars->trans('modal.rmp.cancel.text');
        $this->jsVars->trans('modal.rmp.cancel.confirm_btn');
        $this->jsVars->trans('modal.rmp.cancel.confirm_message');
        $this->jsVars->trans('modal.pricer.upload.success');
        $this->jsVars->trans('modal.pricer.upload.error');
        $this->jsVars->trans('modal.pricer.upload.something_wrong');
        $this->jsVars->trans('modal.pricer.delete.success');
        $this->jsVars->trans('modal.pricer.delete.confirm_title');
        $this->jsVars->trans('modal.pricer.delete.confirm_message');
        $this->jsVars->trans('modal.pricer.delete.confirm_yes');
        $this->jsVars->trans('error.maturities.sub_segment');
        $this->jsVars->trans('hedge.hedge_lines.error');
        $this->jsVars->trans('hedge.hedge_lines.error_blotter');
        $this->jsVars->trans('pricer.upload.label');
        $this->jsVars->trans('pricer.upload.label_action');
        $this->jsVars->trans('you');
        $this->jsVars->trans('edit');
        $this->jsVars->trans('delete');
        $this->jsVars->trans('comment.placeholder');

        // Routes
        //API
        $this->jsVars->addRoute('api_get_hedging_tools', ['operationType' => '__operationType__']);
        $this->jsVars->addRoute('api_get_maturities_by_rmp', ['rmp' => '__rmp__']);
        $this->jsVars->addRoute('api_get_maturities_by_maturity', ['maturity' => '__maturity__', 'rmp' => '__rmp__']);
        $this->jsVars->addRoute('api_get_maturities_range', ['rmp' => '__rmp__', 'firstMaturity' => '__firstMaturity__', 'lastMaturity' => '__lastMaturity__', 'subSegment' => '__subSegment__']);
        $this->jsVars->addRoute('api_get_price_risk_classification', ['rmp' => '__rmp__', 'subSegment' => '__subSegment__']);
        $this->jsVars->addRoute('api_get_segments', ['rmp' => '__rmp__']);
        $this->jsVars->addRoute('api_get_sub_segments_by_rmp', ['rmp' => '__rmp__', 'segment' => '__segment__']);
        $this->jsVars->addRoute('api_sort_table', ['entity' => '__entity__', 'field' => '__field__', 'order' => '__order__', 'page' => '__page__']);
        $this->jsVars->addRoute('api_cancel_hedge', ['hedge' => '__hedge__']);
        $this->jsVars->addRoute('api_get_volumes_limits');
        $this->jsVars->addRoute('api_hedge_create_execution_request');
        $this->jsVars->addRoute('api_hedge_execution_request', ['hedge' => '__hedge__']);
        $this->jsVars->addRoute('api_get_hedging_tool_columns', ['hedgingTool' => '__hedgingTool__']);
        $this->jsVars->addRoute('api_write_off_hedge', ['hedge' => '__hedge__']);
        $this->jsVars->addRoute('api_update_selected_bu', ['businessUnit' => '__businessUnit__']);
        $this->jsVars->addRoute('api_hedge_test_generator', ['hedge' => '__hedge__', 'type' => '__type__']);
        $this->jsVars->addRoute('api_comment_remove', ['id' => '__id__', 'type' => '__type__']);
        $this->jsVars->addRoute('api_comment_edit', ['id' => '__id__', 'type' => '__type__']);
        $this->jsVars->addRoute('api_comment_add', ['parentId' => '__parentId__', 'parentClass' => '__parentClass__']);
        $this->jsVars->addRoute('api_get_rmp_sub_segments', ['rmp' => '__rmp__']);
        $this->jsVars->addRoute('api_rmp_sub_segment_comment_modal_content', ['rmpSubSegment' => '__rmpSubSegment__']);
        $this->jsVars->addRoute('api_hedge_comment_modal_content', ['hedge' => '__hedge__']);
        $this->jsVars->addRoute('api_hedge_check_sell_extra_approval');
        $this->jsVars->addRoute('api_hedge_save', ['hedge' => '__hedge__']);
        $this->jsVars->addRoute('api_hedge_save_new');
        $this->jsVars->addRoute('api_currency_get_infos', ['currency' => '__currency__']);
        $this->jsVars->addRoute('api_hedge_check_sell_volume');
        $this->jsVars->addRoute('api_rmp_sub_segment_edit_modal_content', ['rmp' => '__rmp__', 'rmpSubSegment' => '__rmpSubSegment__']);
        $this->jsVars->addRoute('api_rmp_sub_segment_create_modal_content', ['rmp' => '__rmp__', 'segment' => '__segment__']);
        $this->jsVars->addRoute('api_rmp_sub_segment_remove', ['rmpSubSegment' => '__rmpSubSegment__']);
        $this->jsVars->addRoute('api_get_sub_segments_by_segment', ['rmp' => '__rmp__', 'segment' => '__segment__', 'currentRmpSubSegment' => '__currentRmpSubSegment__']);
        $this->jsVars->addRoute('api_get_segment_by_sub_segment', ['subSegment' => '__subSegment__']);
        $this->jsVars->addRoute('api_rmp_block', ['rmp' => '__rmp__', 'block' => '__block__']);
        $this->jsVars->addRoute('api_rmp_approval_request', ['rmp' => '__rmp__']);
        $this->jsVars->addRoute('api_cancel_rmp', ['rmp' => '__rmp__']);
        $this->jsVars->addRoute('api_is_blocked_rmp', ['rmp' => '__rmp__']);
        $this->jsVars->addRoute('api_update_validity_period_rmp', ['rmp' => '__rmp__', 'validityPeriod' => '__validityPeriod__']);
        $this->jsVars->addRoute('api_rmp_get_infos', ['rmp' => '__rmp__', 'subSegment' => '__subSegment__']);
        $this->jsVars->addRoute('api_pricer_update_dashboard', []);
        $this->jsVars->addRoute('api_pricer_delete_file', ['pricer' => '__pricer__']);
        $this->jsVars->addRoute('api_view_alerts', ['type' => '__type__', 'alertIds' => '__alertIds__']);
        $this->jsVars->addRoute('api_read_alert', ['id' => '__id__', 'type' => '__type__']);
        $this->jsVars->addRoute('api_delete_alert', ['id' => '__id__', 'type' => '__type__']);

        //Web
        $this->jsVars->addRoute('hedge_list', ['keepFilters' => '__keepFilters__']);
        $this->jsVars->addRoute('hedge_edit', ['hedge' => '__hedge__', 'saved' => '__saved__']);
        $this->jsVars->addRoute('rmp_view', ['rmp' => '__rmp__', 'segment' => '__segment__']);
        $this->jsVars->addRoute('hedge_generate_blotter', ['hedge' => '__hedge__']);
        $this->jsVars->addRoute('rmp_list', []);
    }
}
