<?php
/**
 * @version 2.0
 * @author Sammy
 *
 * @keywords Samils, ils, php framework
 * -----------------
 * @package Sammy\Packs\HTTP
 * - Autoload, application dependencies
 *
 * MIT License
 *
 * Copyright (c) 2020 Ysare
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */
namespace Sammy\Packs\HTTP {
  use Sammy\Packs\Samils\ApplicationServerHelpers;
  use Sammy\Packs\Sami\Rae\TemplateResolve;
  use Sammy\Packs\Sami\Base\ILeanable;
  use Samils\Handler\Error;
  /**
   * Make sure the module base internal class is not
   * declared in the php global scope defore creating
   * it.
   * It ensures that the script flux is not interrupted
   * when trying to run the current command by the cli
   * API.
   */
  if (!class_exists ('Sammy\Packs\HTTP\Response')) {
  /**
   * @class Response
   * Base internal class for the
   * HTTP module.
   * -
   * This is (in the ils environment)
   * an instance of the php module,
   * wich should contain the module
   * core functionalities that should
   * be extended.
   * -
   * For extending the module, just create
   * an 'exts' directory in the module directory
   * and boot it by using the ils directory boot.
   * -
   */
  class Response {
    use Response\Base;

    function end ($out = null) {
      exit ( $this->sendData ($out) );
    }

    function send ($out = null) {
      exit ($this->sendData ($out));
    }

    function sendData ($data = null) {
      return str ( $this->leanData ($data) );
    }

    function leanData ($data) {
      if (is_object ($data)) {
        $objectClass = get_class ($data);

        $ClassImplements = class_implements ($objectClass);

        if (in_array (ILeanable::class, $ClassImplements)){
          return $data->lean ();
        }
      } elseif (is_array($data)) {
        foreach ($data as $key => $value) {
          $data [ $key ] = $this->leanData ($value);
        }
      }

      return $data;
    }

    private function validLink ($link) {
      return !is_string ($link) ? null : $link;
    }

    function redirect_to ($url = null) {
      if (ob_get_length ()) {
        ob_clean ();
      }

      $url = $this->validLink ($url);

      if ($url) {
        header ('location: ' . $url);
        /**
         * End the request response
         * in order redirecting to the
         * given url
         */
        exit (0);
      }
    }

    function redirect () {
      return call_user_func_array (
        [ $this, 'redirect_to' ], func_get_args ()
      );
    }

    function redirect_back () {
      $request = new Request;

      $referer = $request->getHeader ('referer');

      if (is_string ($referer)) {
        $this->redirect_to ( $referer );
      }
    }

    function render ($template = null, $options = []){
      $options = !is_array ($options) ? [] : $options;
      /**
       * Make sure '$template' is a non
       * empty string that should be a
       * reference for a ils template
       * (view).
       */
      if (is_string ($template) && $template) {
        $templateResolve = new TemplateResolve;

        $layout = 'Application';

        if (isset ($options ['layout']) && is_string ($options ['layout'])) {
          $layout = $options ['layout'];
        }

        $templateDatas = $templateResolve->resolve ($template);

        # View Engine Datas
        $ved = ApplicationServerHelpers::conf ('view-engine');

        #exit (gettype($templateDatas));

        if (!(is_array ($templateDatas) && is_object ($ved))) {
          $error = new Error;

          $error->message = "Could not render {$template} view because of some missing or wrong informations in your application server config file.";

          $error->handle ();

          return false;
        }

        # View Engine
        $ve = $ved->viewEngine;

        $ve::RenderDOM (
          $templateDatas [0],
          array_merge (
            $templateDatas,
            [
              'layout' => $layout,
              'action' => $template
            ],
            $options
          )
        );

        $this->end ();
      }
    }

    function setHeader (string $header, $headerData = null) {
      $headerData = str ($this->leanData ($headerData));
      @header ("$header: $headerData");
      /**
       *
       */
      return $this;
    }

    function status ($statusCode = 200) {
      if (is_numeric ($statusCode)) {
        $this->status = $statusCode;
        @http_response_code ($statusCode);
      }
      /**
       *
       */
      return $this;
    }
  }}
}
