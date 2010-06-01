CakePHP ReST DataSource Plugin
==============================

A CakePHP Plugin containing a DataSource for interarcting with ReSTful web services.

How it works
------------

  - The DataSource issues HTTP requests through CakePHP's HttpSocket class (default) or your own implementation/extension, e.g. an OAuth enabled HttpSocket http://github.com/neilcrookes/http_socket_oauth, passed in in the constructor.
  - It implements create, read, update and delete methods, which are called by Model::save(), Model::find() and Model::delete(). These methods expect your Model object to have a request property in the format defined in HttpSocket::request and add the appropriate HTTP verbs into the array if not already set, e.g. POST, GET, PUT, DELETE.
  - These methods call the public RestSource::request() method passing it an instance of your model object, but you can also access it directly, passing an object with a request property, or an arrayin the format defined in HttpSocket::request or a string which is a URI.
  - The raw response from the HttpSocket::request() is converted into an array, according to the response's content-type header (XML and JSON currently supported), and the result returned on success (determined by 2xx HTTP response code) or boolean false is returned on failure.
  - In addition, if the argument to the request() method was an object, the result is also added to a response property of the object, and if the request failed, and the object has an onError() method, that method will be triggered.

Direct Usage
-----------

 1. Create a model for each thing on web service you want to interact with. For example create a TwitterStatus model
 2. Ensure your model uses this datasource by setting it in the $useDbConfig property, e.g.

    public $useDbConfig = 'Rest.Rest';

 3. Create a method in you model that corresponds to a function you can perform through the web service. E.g.

        public function save($data = null, $validate = true, $fieldList = array()) {
          $this->request = array(
            'uri' => array(
              'host' => 'twitter.com',
              'path' => 'statuses/update.json'
            ),
            'body' => array(
              'status' => $data['TwitterStatus']['text']
            )
          );
          return parent::save($data, $validate, $fieldList);
        }

 4. Anywhere in your application, call:

        $result = ClassRegistry::init('TwitterStatus')->save(array('TwitterStatus' => array('text' => 'Hello World!')));

N.B. This example is purely representative and does not include authentication for example, but demonstrate the general idea.

Other Uses
--------------

I abstracted this functionality out of a lot of other datasources I was writing and I use this extensively as a base data source which I extend for each web service I need, handling the specifics of each of those web services, such as Authentication, in those classes, by overloading each of the public methods in the Rest datasource as required.

For example, you can create a TwitterSource which extends RestSource and overload the constructor to pass in an instance of my <a href="http://www.neilcrookes.com/2010/04/12/cakephp-oauth-extension-to-httpsocket">HttpSocketOauth extension to CakePHP's HttpSocket</a> class, and the request() method, adding in the common request params such as the host key and '.json' on the end of the path key, and the specific authentication params, then just call parent::request() to handle the issuing of the request and parsing of the response.