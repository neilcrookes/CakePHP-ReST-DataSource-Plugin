CakePHP-ReST-DataSource-Plugin
==============================

A CakePHP Plugin containing a DataSource for interarcting with ReSTful web services.

How it works
------------

  - The Data Source uses CakePHP's HttpSocket class to issue HTTP requests and parse responses.
  - It implements Create, Read, Update and Delete methods, adding the appropriate HTTP verbs into the request if not already set.
  - The response is converted into an array according from the raw response according to the content-type header (XML and JSON currently supported), and the response returned on success (determined by 2xx HTTP response code) or boolean false is returned on failure.
  - In addition, if the request is triggered through a normal model find, save or delete method, and the model has an onError() method, that method will be triggered.
  - The request() method is where all this happens and is called by the CRUD methods, but can also be called directly, and you can pass an object with a request property in the format expected by HttpSocket::request, or an array in the format expected by HttpSocket::request or a string which is a URI.

Basic Usage
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

Advanced Usage
--------------

I use this extensively as a base data source which I extend for each web service I need, handling the specifics of each of those web services, such as Authentication, in those classes, by overloading each of the public methods in the Rest datasource as required.

For example, you can create a TwitterSource which extends RestSource and overload the request() method, adding in the common request params such as the host key and '.json' on the end of the path key, and the authentication params, then just call parent::request() to handle the issuing of the request and parsing of the response.