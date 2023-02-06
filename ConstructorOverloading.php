<?php

/**
 ***Use in your class how example:**
 * @internal <pre>
final class FRAntispamHiddenField implements IFormRequest
{
>	use ConstructorOverloading;

>	private array $fieldNames;
>	private ISpamRobotAPI $api;
>	private IFormRequest $request;

>	public function __construct(IFormRequest $obj)
>	{
>	  $this->fieldNames = [];
>	  $this->api = new NullSpamRobotAPI;
>	  $this->request = $obj;
>	  self::overload(func_get_args());
>	}

>	protected function constructIFormRequestString(IFormRequest $obj, string $field)
>	{
>	  $this->fieldNames = [$field];
>	}

>	protected function constructIFormRequestSArrayISpamRobotAPI(IFormRequest $obj, array $fields, ISpamRobotAPI $robotApi)
>	{
>	  $this->fieldNames = $fields;
>	  $this->api = $robotApi;
>	}
}
 * </pre>
 *
 */
trait ConstructorOverloading
{
	/**
	 * Overloaded your constructor
	 *
	 * @param array $args arguments passed to constructor
	 * @return void
	 */
	protected function overload(array $args): void
	{
		$types = array_map(fn($i) => ucfirst(gettype($i)), $args);
		$types = $this->clarifyTypeArray($args, $types);
		$types = $this->clarifyTypeObject($args, $types);
		$methods = $this->getMethodNames($types);
		$prefix = 'construct';
		foreach ($methods as $nameMethod) {
			if (method_exists($this, $prefix . $nameMethod)) {
				call_user_func_array([$this, $prefix . $nameMethod], $args);
				break;
			}
		}
	}
	
	/**
	 * Forms an array with the names of all the possible methods
	 * that might have been defined for the constructor overload.
	 *
	 * @param array $types prepared type names to be combined into method names later
	 * @return array
	 */
	private function getMethodNames(array $types): array
	{
		foreach ($types as $k => $item) {
			if (is_string($item)) {
				$types[$k] = [$item];
			}
			if (is_array($item)) {
				$types[$k] = $item;
			}
		}
		$types = array_reverse($types);
		return $this->processBranch($types, []);
	}
	
	/**
	 * Recursively glue type names in the order they are passed to the client constructor
	 *
	 * @param array $tree
	 * @param array $result
	 * @return array
	 */
	private function processBranch(array $tree, array $result): array
	{
		if (count($tree) > 0) {
			if (is_array($tree[0])) {
				foreach ($tree as $item) {
					$result = $this->processBranch($item, $result);
				}
			} else {
				if (count($result) == 0) {
					foreach ($tree as $item) {
						$result[] = $item;
					}
				} else {
					$tmp = [];
					foreach ($tree as $item) {
						foreach ($result as $elem) {
							$tmp[] = $item . $elem;
						}
					}
					$result = $tmp;
				}
			}
		}
		return $result;
	}
	
	/**
	 * @param array $args
	 * @param array $types
	 * @return array
	 */
	private function clarifyTypeArray(array $args, array $types): array
	{
		$arrays = array_filter($args, fn($v) => is_array($v) && count($v));
		if (count($arrays) > 0) {
			foreach ($arrays as $k => $v) {
				$types[$k] = $this->getNameTypeArray($v);
			}
		}
		return $types;
	}
	
	/**
	 * @param array $array
	 * @return string
	 */
	private function getNameTypeArray(array $array): string
	{
		$nameMethod = '';
		if (count($array) > 0) {
			switch (count($array)) {
				case count(array_filter($array, fn ($i) => is_int($i))) :
					$nameMethod = 'IArray';
					break;
				case count(array_filter($array, fn ($i) => is_float($i))) :
					$nameMethod = 'DArray';
					break;
				case count(array_filter($array, fn ($i) => is_string($i))) :
					$nameMethod = 'SArray';
					break;
				case count(array_filter($array, fn ($i) => is_bool($i))) :
					$nameMethod = 'BArray';
					break;
				case count(array_filter($array, fn ($i) => is_object($i))) :
					$nameMethod = 'OArray';
					break;
				default:
					$nameMethod = 'Array';
					break;
			}
		}
		return $nameMethod;
	}
	
	/**
	 * @param array $args
	 * @param array $types
	 * @return array
	 */
	private function clarifyTypeObject(array $args, array $types): array
	{
		$objects = array_filter($args, fn($v) => is_object($v));
		if (count($objects) > 0) {
			foreach ($objects as $k => $obj) {
				$interfaceNames = class_implements($obj,false);
				if ($interfaceNames !== false && count($interfaceNames) > 0) {
					$interfaceNames = array_map(fn($i) => ucfirst($i), array_values($interfaceNames));
				} else {
					$interfaceNames[] = 'Object';
				}
				$types[$k] = $interfaceNames;
			}
		}
		return $types;
	}
}
