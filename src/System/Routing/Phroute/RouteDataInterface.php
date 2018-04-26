<?php
namespace PytoMVC\System\Routing\Phroute;


/**
 * Interface RouteDataInterface
 * @package PytoMVC\System\Routing\Phroute
 */
interface RouteDataInterface {

    public function mergeRouteDataArray(RouteDataInterface $data);

    /**
     * @return array
     */
    public function getStaticRoutes();

    /**
     * @return array
     */
    public function getVariableRoutes();

    /**
     * @return array
     */
    public function getFilters();
}
