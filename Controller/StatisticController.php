<?php
/*************************************************************************************/
/*      This file is part of the Thelia package.                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : dev@thelia.net                                                       */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE.txt  */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

namespace Statistic\Controller;

use DateInterval;
use Propel\Runtime\ActiveQuery\Criteria;
use Statistic\Statistic;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Model\OrderQuery;

/**
 * Class StatisticController
 * @package Statistic\Controller
 * @author David Gros <dgros@openstudio.fr>
 */
class StatisticController extends BaseAdminController
{

    /**
     * Display statistic page.
     *
     * fr_FR Affichage de la page de statistique.
     */
    public function toolShow()
    {
        return $this->render('statistic-tool');
    }

    /**
     * @return \Thelia\Core\HttpFoundation\Response
     * @throws \Exception
     */
    public function statAverageCartAction()
    {
        // récupération des paramètres
        /*$month = $this->getRequest()->query->get('month', date('m'));
        $year = $this->getRequest()->query->get('year', date('m'));*/

        $this->getRequest()->getSession()->save();

        $ghost = $this->getRequest()->query->get('ghost');

        $startDay = $this->getRequest()->query->get('startDay', date('d'));
        $startMonth = $this->getRequest()->query->get('startMonth', date('m'));
        $startYear = $this->getRequest()->query->get('startYear', date('Y'));

        $endDay = $this->getRequest()->query->get('endDay', date('d'));
        $endMonth = $this->getRequest()->query->get('endMonth', date('m'));
        $endYear = $this->getRequest()->query->get('endYear', date('Y'));

        $startDate = new \DateTime($startYear . '-' . $startMonth . '-' . $startDay);
        $endDate = new \DateTime($endYear . '-' . $endMonth . '-' . ($endDay+1));

        $result = $this->getStatisticHandler()->averageCart($startDate, $endDate);
        $average = new \stdClass();
        $average->color = '#5cb85c';
        $average->graph = $result['stats'];
        $average->graphLabel = $result['label'];

        $data = new \stdClass();

        if ($startDay == $endDay && $startMonth == $endMonth && $startYear == $endYear) {
            $data->title = $this->getTranslator()->trans("Stats between %startDay/%startMonth/%startYear", array(
                '%startDay'=>$startDay,
                '%startMonth' => $startMonth,
                '%startYear' => $startYear,
            ), Statistic::MESSAGE_DOMAIN);
        }
        else {
            $data->title = $this->getTranslator()->trans("Stats between %startDay/%startMonth/%startYear and %endDay/%endMonth/%endYear", array(
                '%startDay'=>$startDay,
                '%startMonth' => $startMonth,
                '%startYear' => $startYear,
                '%endDay'=>$endDay,
                '%endMonth'=>$endMonth,
                '%endYear'=>$endYear
            ), Statistic::MESSAGE_DOMAIN);
        }

        $data->series = array(
            $average,
        );

        if ($ghost == 1){

            $ghostGraph = $this->getStatisticHandler()->averageCart(
                $startDate->sub(new DateInterval('P1Y')),
                $endDate->sub(new DateInterval('P1Y'))
            );
            $ghostCurve = new \stdClass();
            $ghostCurve->color = "#38acfc";
            $ghostCurve->graph = $ghostGraph['stats'];

            array_push($data->series, $ghostCurve);
        }

        return $this->jsonResponse(json_encode($data));
    }

    public function statBestSalesAction()
    {
        // récupération des paramètres
        $startDay = $this->getRequest()->query->get('startDay', date('d'));
        $startMonth = $this->getRequest()->query->get('startMonth', date('m'));
        $startYear = $this->getRequest()->query->get('startYear', date('Y'));

        $endDay = $this->getRequest()->query->get('endDay', date('d'));
        $endMonth = $this->getRequest()->query->get('endMonth', date('m'));
        $endYear = $this->getRequest()->query->get('endYear', date('Y'));

        $startDate = new \DateTime($startYear . '-' . $startMonth . '-' . $startDay);
        $endDate = new \DateTime($endYear . '-' . $endMonth . '-' . $endDay);

        $bestSales = new \stdClass();
        $bestSales->color = '#5cb85c';
        $bestSales->thead = array(
            'title' => $this->getTranslator()->trans('tool.panel.general.bestSales.name', [], Statistic::MESSAGE_DOMAIN),
            'pse_ref' => $this->getTranslator()->trans('tool.panel.general.bestSales.reference', [], Statistic::MESSAGE_DOMAIN),
            'total_sold' => $this->getTranslator()->trans('tool.panel.general.bestSales.totalSold', [], Statistic::MESSAGE_DOMAIN),
            'total_ht' => $this->getTranslator()->trans('tool.panel.general.bestSales.totalHT', [], Statistic::MESSAGE_DOMAIN),
            'total_ttc' => $this->getTranslator()->trans('tool.panel.general.bestSales.totalTTC', [], Statistic::MESSAGE_DOMAIN),
        );
        $bestSales->table = $this->getStatisticHandler()->bestSales($this->getRequest(), $startDate, $endDate);

        $data = new \stdClass();
        $data->series = array(
            $bestSales,
        );

        return $this->jsonResponse(json_encode($data));
    }

    public function statDiscountCodeAction()
    {
        // récupération des paramètres
        $startDay = $this->getRequest()->query->get('startDay', date('d'));
        $startMonth = $this->getRequest()->query->get('startMonth', date('m'));
        $startYear = $this->getRequest()->query->get('startYear', date('Y'));

        $endDay = $this->getRequest()->query->get('endDay', date('d'));
        $endMonth = $this->getRequest()->query->get('endMonth', date('m'));
        $endYear = $this->getRequest()->query->get('endYear', date('Y'));

        $startDate = new \DateTime($startYear . '-' . $startMonth . '-' . $startDay);
        $endDate = new \DateTime($endYear . '-' . $endMonth . '-' . $endDay);

        $discount = new \stdClass();
        $result = $this->getStatisticHandler()->discountCode($startDate, $endDate);
        foreach( $result as &$coupon ){
            /** @var \Thelia\Coupon\Type\CouponInterface $couponService */
            $couponService = $this->getSpecificCouponService($coupon['type']);
            $coupon['rule'] = $couponService->getName();
        }
        $discount->table = $result;
        $discount->thead = array(
            'code' => $this->getTranslator()->trans('tool.panel.general.discountCode.code',[], Statistic::MESSAGE_DOMAIN),
            'rule' => $this->getTranslator()->trans('tool.panel.general.discountCode.rule',[], Statistic::MESSAGE_DOMAIN),
            'total' => $this->getTranslator()->trans('tool.panel.general.discountCode.nbUse',[], Statistic::MESSAGE_DOMAIN),
        );

        $data = new \stdClass();
        $data->series = array(
            $discount,
        );

        return $this->jsonResponse(json_encode($data));
    }

    public function statMeansTransportAction()
    {
        // récupération des paramètres
        $startDay = $this->getRequest()->query->get('startDay', date('d'));
        $startMonth = $this->getRequest()->query->get('startMonth', date('m'));
        $startYear = $this->getRequest()->query->get('startYear', date('Y'));

        $endDay = $this->getRequest()->query->get('endDay', date('d'));
        $endMonth = $this->getRequest()->query->get('endMonth', date('m'));
        $endYear = $this->getRequest()->query->get('endYear', date('Y'));

        $startDate = new \DateTime($startYear . '-' . $startMonth . '-' . $startDay);
        $endDate = new \DateTime($endYear . '-' . $endMonth . '-' . $endDay);

        $local = $this->getSession()->getLang()->getLocale();

        $transport = new \stdClass();
        $transport->table = $this->getStatisticHandler()->meansTransport($startDate, $endDate, $local);
        $transport->thead = array(
            'code' => $this->getTranslator()->trans('tool.panel.general.meansTransport.means',[], Statistic::MESSAGE_DOMAIN),
            'title' => $this->getTranslator()->trans('tool.panel.general.meansTransport.description',[], Statistic::MESSAGE_DOMAIN),
            'total' => $this->getTranslator()->trans('tool.panel.general.meansTransport.nbUse',[], Statistic::MESSAGE_DOMAIN),
        );

        $data = new \stdClass();
        $data->series = array(
            $transport,
        );

        return $this->jsonResponse(json_encode($data));
    }

    public function statMeansPaymentAction()
    {
        // récupération des paramètres
        $startDay = $this->getRequest()->query->get('startDay', date('d'));
        $startMonth = $this->getRequest()->query->get('startMonth', date('m'));
        $startYear = $this->getRequest()->query->get('startYear', date('Y'));

        $endDay = $this->getRequest()->query->get('endDay', date('d'));
        $endMonth = $this->getRequest()->query->get('endMonth', date('m'));
        $endYear = $this->getRequest()->query->get('endYear', date('Y'));

        $startDate = new \DateTime($startYear . '-' . $startMonth . '-' . $startDay);
        $endDate = new \DateTime($endYear . '-' . $endMonth . '-' . $endDay);

        $local = $this->getSession()->getLang()->getLocale();

        $payment = new \stdClass();
        $payment->table = $this->getStatisticHandler()->meansPayment($startDate, $endDate, $local);
        $payment->thead = array(
            'code' => $this->getTranslator()->trans('tool.panel.general.meansPayment.means',[], Statistic::MESSAGE_DOMAIN),
            'title' => $this->getTranslator()->trans('tool.panel.general.meansPayment.description',[], Statistic::MESSAGE_DOMAIN),
            'total' => $this->getTranslator()->trans('tool.panel.general.meansPayment.nbUse',[], Statistic::MESSAGE_DOMAIN),
        );

        $data = new \stdClass();
        $data->series = array(
            $payment,
        );

        return $this->jsonResponse(json_encode($data));
    }

    public function statTurnoverAction()
    {
        $this->getRequest()->getSession()->save();
        setlocale (LC_TIME, 'fr_FR.utf8','fra');

        // récupération des paramètres

        $startYear = $this->getRequest()->query->get('startYear', date('Y'));
        $endYear = $this->getRequest()->query->get('endYear', date('Y'));

        $result[$startYear] = $this->getStatisticHandler()->getTurnoverYear($startYear);

        $turnoverStart = new \stdClass();

        $turnoverStart->color = '#adadad';
        $turnoverStart->graph = $result[$startYear]['graph'];
        $turnoverStart->graphLabel = $result[$startYear]['month'];
        $turnoverStart->table = $result[$startYear]['table'];
        $turnoverStart->thead = array(
            'month' => $this->getTranslator()->trans('tool.panel.general.turnover.month', [], Statistic::MESSAGE_DOMAIN),
            'TTCWithShippping' => $this->getTranslator()->trans('tool.panel.general.turnover.TTCWithShippping', [], Statistic::MESSAGE_DOMAIN),
            'TTCWithoutShippping' => $this->getTranslator()->trans('tool.panel.general.turnover.TTCWithoutShippping', [], Statistic::MESSAGE_DOMAIN),
        );

        $data = new \stdClass();

        $data->series = array(
            $turnoverStart,
        );

        if ($startYear != $endYear) {
            $result[$endYear] = $this->getStatisticHandler()->getTurnoverYear($endYear);

            $turnoverEnd = new \stdClass();

            $turnoverEnd->color = '#F00';
            $turnoverEnd->graph = $result[$endYear]['graph'];
            $turnoverEnd->graphLabel = $result[$endYear]['month'];
            $turnoverEnd->table = $result[$endYear]['table'];
            $turnoverEnd->thead = array(
                'month' => $this->getTranslator()->trans('tool.panel.general.turnover.month', [], Statistic::MESSAGE_DOMAIN),
                'TTCWithShippping' => $this->getTranslator()->trans('tool.panel.general.turnover.TTCWithShippping', [], Statistic::MESSAGE_DOMAIN),
                'TTCWithoutShippping' => $this->getTranslator()->trans('tool.panel.general.turnover.TTCWithoutShippping', [], Statistic::MESSAGE_DOMAIN),
            );
            array_push($data->series, $turnoverEnd);
            $data->title = $this->getTranslator()->trans("Stats on %startYear and %endYear", array('%startYear' => $startYear, '%endYear' => $endYear), Statistic::MESSAGE_DOMAIN);
        }
        else
            $data->title = $this->getTranslator()->trans("Stats on %startYear", array('%startYear' => $startYear), Statistic::MESSAGE_DOMAIN);

        return $this->jsonResponse(json_encode($data));
    }

    /**
     * @return \Thelia\Core\HttpFoundation\Response
     * @throws \Exception
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function statRevenueAction()
    {
        $this->getRequest()->getSession()->save();
        $ghost = $this->getRequest()->query->get('ghost');

        $startDay = $this->getRequest()->query->get('startDay', date('d'));
        $startMonth = $this->getRequest()->query->get('startMonth', date('m'));
        $startYear = $this->getRequest()->query->get('startYear', date('Y'));

        $endDay = $this->getRequest()->query->get('endDay', date('d'));
        $endMonth = $this->getRequest()->query->get('endMonth', date('m'));
        $endYear = $this->getRequest()->query->get('endYear', date('Y'));

        $startDate = new \DateTime($startYear . '-' . $startMonth . '-' . $startDay);
        $endDate = new \DateTime($endYear . '-' . $endMonth . '-' . $endDay);

        $saleSeries = new \stdClass();

        if ($startDate->diff($endDate)->format('%a') === '0') {
            $result = $this->getStatisticHandler()->getRevenueStatsByHours($startDate);
        }
        else{
            $endDate->add(new DateInterval('P1D'));
            $result = $this->getStatisticHandler()->getRevenueStats($startDate,$endDate);
        }
        $saleSeries->color = '#adadad';
        $saleSeries->graph = $result['stats'];
        $saleSeries->graphLabel = $result['label'];

        $data = new \stdClass();

        if ($startDay == $endDay && $startMonth == $endMonth && $startYear == $endYear) {
            $data->title = $this->getTranslator()->trans("Stats between %startDay/%startMonth/%startYear", array(
                '%startDay'=>$startDay,
                '%startMonth' => $startMonth,
                '%startYear' => $startYear,
            ), Statistic::MESSAGE_DOMAIN);
        }
        else {
            $data->title = $this->getTranslator()->trans("Stats between %startDay/%startMonth/%startYear and %endDay/%endMonth/%endYear", array(
                '%startDay'=>$startDay,
                '%startMonth' => $startMonth,
                '%startYear' => $startYear,
                '%endDay'=>$endDay,
                '%endMonth'=>$endMonth,
                '%endYear'=>$endYear
            ), Statistic::MESSAGE_DOMAIN);
        }

        $data->series = array(
            $saleSeries,
        );

        if ($ghost == 1){
            if ($startDate->diff($endDate)->format('%a') === '0') {
                $ghostGraph = $this->getStatisticHandler()->getRevenueStatsByHours($startDate->sub(new DateInterval('P1Y')));
            }
            else{
                $ghostGraph = $this->getStatisticHandler()->getRevenueStats(
                    $startDate->sub(new DateInterval('P1Y')),
                    $endDate->sub(new DateInterval('P1Y'))
                );
            }
            $ghostCurve = new \stdClass();
            $ghostCurve->color = "#38acfc";
            $ghostCurve->graph = $ghostGraph['stats'];

            array_push($data->series, $ghostCurve);
        }

        return $this->jsonResponse(json_encode($data));
    }

    /**
     * @return \Thelia\Core\HttpFoundation\Response
     * @throws \Exception
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function statOrdersAction()
    {
        $this->getRequest()->getSession()->save();
        $ghost = $this->getRequest()->query->get('ghost');

        $startDay = $this->getRequest()->query->get('startDay', date('d'));
        $startMonth = $this->getRequest()->query->get('startMonth', date('m'));
        $startYear = $this->getRequest()->query->get('startYear', date('Y'));

        $endDay = $this->getRequest()->query->get('endDay', date('d'));
        $endMonth = $this->getRequest()->query->get('endMonth', date('m'));
        $endYear = $this->getRequest()->query->get('endYear', date('Y'));

        $startDate = new \DateTime($startYear . '-' . $startMonth . '-' . $startDay);
        $endDate = new \DateTime($endYear . '-' . $endMonth . '-' . $endDay);

        $saleSeries = new \stdClass();


        if ($startDate->diff($endDate)->format('%a') === '0') {
            $result = $this->getStatisticHandler()->getOrdersStatsByHours($startDate);
        }
        else{
            $endDate->add(new DateInterval('P1D'));
            $result = $this->getStatisticHandler()->getOrdersStats($startDate,$endDate);
        }
        $saleSeries->color = '#d10d0d';
        $saleSeries->graph = $result['stats'];
        $saleSeries->graphLabel = $result['label'];

        $data = new \stdClass();

        if ($startDay == $endDay && $startMonth == $endMonth && $startYear == $endYear) {
            $data->title = $this->getTranslator()->trans("Stats between %startDay/%startMonth/%startYear", array(
                '%startDay'=>$startDay,
                '%startMonth' => $startMonth,
                '%startYear' => $startYear,
            ), Statistic::MESSAGE_DOMAIN);
        }
        else {
            $data->title = $this->getTranslator()->trans("Stats between %startDay/%startMonth/%startYear and %endDay/%endMonth/%endYear", array(
                '%startDay'=>$startDay,
                '%startMonth' => $startMonth,
                '%startYear' => $startYear,
                '%endDay'=>$endDay,
                '%endMonth'=>$endMonth,
                '%endYear'=>$endYear
            ), Statistic::MESSAGE_DOMAIN);
        }

        $data->series = array(
            $saleSeries,
        );

        if ($ghost == 1){
            if ($startDate->diff($endDate)->format('%a') === '0') {
                $ghostGraph = $this->getStatisticHandler()->getOrdersStatsByHours($startDate->sub(new DateInterval('P1Y')));
            }
            else{
                $ghostGraph = $this->getStatisticHandler()->getOrdersStats(
                    $startDate->sub(new DateInterval('P1Y')),
                    $endDate->sub(new DateInterval('P1Y'))
                );
            }
            $ghostCurve = new \stdClass();
            $ghostCurve->color = "#38acfc";
            $ghostCurve->graph = $ghostGraph['stats'];

            array_push($data->series, $ghostCurve);
        }

        return $this->jsonResponse(json_encode($data));
    }

    /** @var  \Statistic\Handler\StatisticHandler */
    protected $statisticHandler;

    protected function getStatisticHandler()
    {
        if (!isset($this->statisticHandler)) {
            $this->statisticHandler = $this->getContainer()->get('statistic.handler.statistic');
        }

        return $this->statisticHandler;
    }

    /** @var  \Thelia\Coupon\Type\CouponInterface */
    protected $couponsServices = array();

    protected function getSpecificCouponService( $serviceId)
    {
        if( !isset( $this->couponsServices[$serviceId])){
            $this->couponsServices[$serviceId] = $this->getContainer()->get($serviceId);
        }
        return $this->couponsServices[$serviceId];
    }

}