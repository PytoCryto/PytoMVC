<?php
namespace PytoMVC\System\Routing\Phroute;

class RouteDataArray implements RouteDataInterface {

    /**
     * @var array
     */
    private $variableRoutes;

    /**
     * @var array
     */
    private $staticRoutes;

    /**
     * @var array
     */
    private $filters;

    /**
     * @var array
     */
    private $beforeAll;

    /**
     * @param array $staticRoutes
     * @param array $variableRoutes
     * @param array $filters
     */
    public function __construct(array $staticRoutes, array $variableRoutes, array $filters, $beforeAll = null)
    {
        $this->staticRoutes = $staticRoutes;

        $this->variableRoutes = $variableRoutes;

        $this->filters = $filters;

        $this->beforeAll = $beforeAll;
    }

    public function mergeRouteDataArray(RouteDataInterface $data)
    {
        $this->staticRoutes = array_merge($this->getStaticRoutes(), $data->getStaticRoutes());

        $this->variableRoutes = array_merge($this->getVariableRoutes(), $data->getVariableRoutes());

        $this->filters = array_merge($this->getFilters(), $data->getFilters());
    }

    /**
     * @return array
     */
    public function getStaticRoutes()
    {
        return $this->staticRoutes;
    }

    /**
     * @return array
     */
    public function getVariableRoutes()
    {
        return $this->variableRoutes;
    }

    /**
     * @return mixed
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * @return mixed
     */
    public function getBeforeAll()
    {
        return $this->beforeAll;
    }
}
