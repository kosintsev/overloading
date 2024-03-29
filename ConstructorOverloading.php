<?php

namespace App;
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
		$prefix = 'construct';
		$nameMethod = $prefix . implode('', $types);
		if (method_exists($this, $nameMethod)) {
			call_user_func_array([$this, $nameMethod], $args);
		} else {
			$types = $this->clarifyTypeArray($args, $types);
			$types = $this->clarifyTypeObject($args, $types);
			$methods = $this->getMethodNames($types);
			foreach ($methods as $nameMethod) {
				if (method_exists($this, $prefix . $nameMethod)) {
					call_user_func_array([$this, $prefix . $nameMethod], $args);
					break;
				}
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
	 * If arguments contain a non-empty array, specify what type of value list it is.
	 *
	 * @param array $args  arguments passed to constructor
	 * @param array $types type names to be qualified
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
	 * If the array consists entirely of values of the same type,
	 * then return the corresponding type name for the array
	 *
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
	 * Clarify which interfaces are implemented
	 *
	 * @param array $args  arguments passed to constructor
	 * @param array $types type names to be qualified
	 * @return array
	 */
	private function clarifyTypeObject(array $args, array $types): array
	{
		$objects = array_filter($args, fn($v) => is_object($v));
		if (count($objects) > 0) {
			foreach ($objects as $k => $obj) {
				$interfaceNames = class_implements($obj,false);
				if ($interfaceNames !== false && count($interfaceNames) > 0) {
					$interfaceNames = array_map(fn($i) => ucfirst(
						array_slice(explode("\\", $i), -1, 1)[0]// todo: refactoring, namespaces prepare properly
					), array_values($interfaceNames));
				} else {
					$interfaceNames = array_slice(explode("\\", get_class($obj)), -1, 1)[0];
				}
				$types[$k] = $interfaceNames;
			}
		}
		return $types;
	}
}
