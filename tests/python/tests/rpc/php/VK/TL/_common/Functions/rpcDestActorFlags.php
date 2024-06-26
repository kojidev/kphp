<?php

/**
 * AUTOGENERATED, DO NOT EDIT! If you want to modify it, check tl schema.
 *
 * This autogenerated code represents tl class for typed RPC API.
 */

namespace VK\TL\_common\Functions;

use VK\TL;

/**
 * @kphp-tl-class
 */
class rpcDestActorFlags implements TL\RpcFunction {

  /** @var int */
  public $actor_id = 0;

  /** @var int */
  public $flags = 0;

  /** @var TL\_common\Types\rpcInvokeReqExtra */
  public $extra = null;

  /** @var TL\RpcFunction */
  public $query = null;

  /** Allows kphp implicitly load function result class */
  private const RESULT = TL\_common\Functions\rpcDestActorFlags_result::class;

  /**
   * @param int $actor_id
   * @param int $flags
   * @param TL\_common\Types\rpcInvokeReqExtra $extra
   * @param TL\RpcFunction $query
   */
  public function __construct($actor_id = 0, $flags = 0, $extra = null, $query = null) {
    $this->actor_id = $actor_id;
    $this->flags = $flags;
    $this->extra = $extra;
    $this->query = $query;
  }

  /**
   * @param TL\RpcFunctionReturnResult $function_return_result
   * @return TL\RpcFunctionReturnResult
   */
  public static function functionReturnValue($function_return_result) {
    if ($function_return_result instanceof rpcDestActorFlags_result) {
      return $function_return_result->value;
    }
    warning('Unexpected result type in functionReturnValue: ' . ($function_return_result ? get_class($function_return_result) : 'null'));
    return (new rpcDestActorFlags_result())->value;
  }

  /**
   * @kphp-inline
   *
   * @param TL\RpcResponse $response
   * @return TL\RpcFunctionReturnResult
   */
  public static function result(TL\RpcResponse $response) {
    return self::functionReturnValue($response->getResult());
  }

  /**
   * @kphp-inline
   *
   * @return string
   */
  public function getTLFunctionName() {
    return 'rpcDestActorFlags';
  }

}

/**
 * @kphp-tl-class
 */
class rpcDestActorFlags_result implements TL\RpcFunctionReturnResult {

  /** @var TL\RpcFunctionReturnResult */
  public $value = null;

}