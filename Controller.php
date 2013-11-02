<?php namespace Illuminate\Routing;

use Closure;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class Controller {

	/**
	 * The "before" filters registered on the controller.
	 *
	 * @var array
	 */
	protected $beforeFilters = array();

	/**
	 * The "after" filters registered on the controller.
	 *
	 * @var array
	 */
	protected $afterFilters = array();

	/**
	 * The layout used by the controller.
	 *
	 * @var \Illuminate\View\View
	 */
	protected $layout;

	/**
	 * The route filterer implementation.
	 *
	 * @var \Illuminate\Routing\RouteFiltererInterface
	 */
	protected static $filterer;

	/**
	 * Create a new Controller instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->setupLayout();
	}

	/**
	 * Register a "before" filter on the controler.
	 *
	 * @param  \Closure|string  $name
	 * @param  array  $options
	 * @return void
	 */
	public function beforeFilter($filter, array $options = array())
	{
		$this->beforeFilters[] = $this->parseFilter($filter, $options);
	}

	/**
	 * Register an "after" filter on the controler.
	 *
	 * @param  \Closure|string  $name
	 * @param  array  $options
	 * @return void
	 */
	public function afterFilter($filter, array $options = array())
	{
		$this->afterFilters[] = $this->parseFilter($filter, $options);
	}

	/**
	 * Parse the given filter and options.
	 *
	 * @param  \Closure|string  $name
	 * @param  array  $options
	 * @return array
	 */
	protected function parseFilter($filter, array $options)
	{
		$parameters = array();

		if ($filter instanceof Closure)
		{
			$filter = $this->registerClosureFilter($filter);
		}
		elseif ($this->isInstanceFilter($filter))
		{
			$filter = $this->registerInstanceFilter($filter);
		}
		else
		{
			list($filter, $parameters) = Route::parseFilter($filter);
		}

		return compact('filter', 'parameters', 'options');
	}

	/**
	 * Register an anonymous controller filter Closure.
	 *
	 * @param  \Closure  $filter
	 * @return string
	 */
	protected function registerClosureFilter(Closure $filter)
	{
		$this->getFilterer()->filter($name = spl_object_hash($filter), $filter);

		return $name;
	}

	/**
	 * Register a controller instance method as a filter.
	 *
	 * @param  string  $filter
	 * @return string
	 */
	protected function registerInstanceFilter($filter)
	{
		$this->getFilterer()->filter($filter, array($this, substr($filter, 1)));

		return $filter;
	}

	/**
	 * Determine if a filter is a local method on the controller.
	 *
	 * @param  mixed  $filter
	 * @return boolean
	 */
	protected function isInstanceFilter($filter)
	{
		if (is_string($filter) && starts_with($filter, '@'))
		{
			if (method_exists($this, substr($filter, 1))) return true;

			throw new \InvalidArgumentException("Filter method [$filter] does not exist.");
		}

		return false;
	}

	/**
	 * Get the registered "before" filters.
	 *
	 * @return array
	 */
	public function getBeforeFilters()
	{
		return $this->beforeFilters;
	}

	/**
	 * Get the registered "after" filters.
	 *
	 * @return array
	 */
	public function getAfterFilters()
	{
		return $this->afterFilters;
	}

	/**
	 * Get the route filterer implementation.
	 *
	 * @return \Illuminate\Routing\RouteFiltererInterface
	 */
	public static function getFilterer()
	{
		return static::$filterer;
	}

	/**
	 * Set the route filterer implementation.
	 *
	 * @param  \Illuminate\Routing\RouteFiltererInterface  $filterer
	 * @return void
	 */
	public static function setFilterer(RouteFiltererInterface $filterer)
	{
		static::$filterer = $filterer;
	}

	/**
	 * Get the layout used by the controler.
	 *
	 * @return \Illuminate\View\View|null
	 */
	public function getLayout()
	{
		return $this->layout;
	}

	/**
	 * Setup the layout used by the controller.
	 *
	 * @return void
	 */
	protected function setupLayout() {}

	/**
	 * Handle calls to missing methods on the controller.
	 *
	 * @param  array  $parameters
	 * @return mixed
	 */
	public function missingMethod($parameters)
	{
		throw new NotFoundHttpException("Controller method not found.");
	}

	/**
	 * Handle calls to missing methods on the controller.
	 *
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		return $this->missingMethod($parameters);
	}

}