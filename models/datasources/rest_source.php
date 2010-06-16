<?php
/**
 * DataSource for interacting with REST APIs
 *
 * @author Neil Crookes <neil@neilcrookes.com>
 * @link http://www.neilcrookes.com
 * @copyright (c) 2010 Neil Crookes
 * @license MIT License - http://www.opensource.org/licenses/mit-license.php
 */
class RestSource extends DataSource {

  /**
   * The description of this data source
   *
   * @var string
   */
  public $description = 'Rest DataSource';

  /**
   * Instance of CakePHP core HttpSocket class
   *
   * @var HttpSocket
   */
  public $Http = null;

  /**
   * Loads HttpSocket class
   *
   * @param array $config
   * @param HttpSocket $Http
   */
  public function __construct($config, $Http = null) {
    parent::__construct($config);
    if (!$Http) {
      App::import('Core', 'HttpSocket');
      $Http = new HttpSocket();
    }
    $this->Http = $Http;
  }

  /**
   * Sets method = POST in request if not already set
   *
   * @param AppModel $model
   * @param array $fields Unused
   * @param array $values Unused
   */
  public function create(&$model, $fields = null, $values = null) {
    $model->request = array_merge(array('method' => 'POST'), $model->request);
    return $this->request($model);
  }

  /**
   * Sets method = GET in request if not already set
   *
   * @param AppModel $model
   * @param array $queryData Unused
   */
  public function read(&$model, $queryData = array()) {
    $model->request = array_merge(array('method' => 'GET'), $model->request);
    return $this->request($model);
  }

  /**
   * Sets method = PUT in request if not already set
   *
   * @param AppModel $model
   * @param array $fields Unused
   * @param array $values Unused
   */
  public function update(&$model, $fields = null, $values = null) {
    $model->request = array_merge(array('method' => 'PUT'), $model->request);
    return $this->request($model);
  }

  /**
   * Sets method = DELETE in request if not already set
   *
   * @param AppModel $model
   * @param mixed $id Unused
   */
  public function delete(&$model, $id = null) {
    $model->request = array_merge(array('method' => 'DELETE'), $model->request);
    return $this->request($model);
  }

  /**
   * Issues request and returns response as an array decoded according to the
   * response's content type if the response code is 200, else triggers the
   * $model->onError() method (if it exists) and finally returns false.
   *
   * @param mixed $model Either a CakePHP model with a request property, or an
   * array in the format expected by HttpSocket::request or a string which is a
   * URI.
   * @return mixed The response or false
   */
  public function request(&$model) {

    if (is_object($model)) {
      $request = $model->request;
    } elseif (is_array($model)) {
      $request = $model;
    } elseif (is_string($model)) {
      $request = array('uri' => $model);
    }

    // Remove unwanted elements from request array
    $request = array_intersect_key($request, $this->Http->request);

    // Issues request
    $response = $this->Http->request($request);

    // Get content type header
    $contentType = $this->Http->response['header']['Content-Type'];

    // Extract content type from content type header
    if (preg_match('/^([a-z0-9\/\+]+);\s*charset=([a-z0-9\-]+)/i', $contentType, $matches)) {
      $contentType = $matches[1];
      $charset = $matches[2];
    }

    // Decode response according to content type
    switch ($contentType) {
    	case 'application/xml':
    	case 'application/atom+xml':
    	case 'application/rss+xml':
        // If making multiple requests that return xml, I found that using the
        // same Xml object with Xml::load() to load new responses did not work,
        // consequently it is necessary to create a whole new instance of the
        // Xml class. This can use a lot of memory so we have to manually
        // garbage collect the Xml object when we've finished with it, i.e. got
        // it to transform the xml string response into a php array.
    	  App::import('Core', 'Xml');
      	$Xml = new Xml($response);
      	$response = $Xml->toArray(false); // Send false to get separate elements
        $Xml->__destruct();
        $Xml = null;
        unset($Xml);
      	break;
      case 'application/json':
      case 'text/javascript':
        $response = json_decode($response, true);
        break;
    }

    if (is_object($model)) {
      $model->response = $response;
    }

    // Check response status code for success or failure
    if (substr($this->Http->response['status']['code'], 0, 1) != 2) {
      if (is_object($model) && method_exists($model, 'onError')) {
        $model->onError();
      }
      return false;
    }

    return $response;

  }

}
?>