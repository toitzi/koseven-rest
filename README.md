# Koseven REST Module
![License](https://img.shields.io/badge/license-BSD--3--Clause-green.svg)

This is a simple REST module for Koseven, which started as a port 
from Kohana's core REST module in 3.1.1.1 and SupersonicAds's REST module.

## :sparkles: Features

* Support for GET/POST/PUT/DELETE methods.
* Encapsulated query and post parameters parsing.
* Multiple output formats - JSON, XML and HTML.
* Method overriding and response code suppressing for limited clients.
* Cache control.
* Attachment header.
* Command line support, using a Minion task.

## :page_facing_up: Basic Usage

After enabling the module in `Kohana::modules`, you must create a route for your application.

Recommended bootstrap route:

	Route::set('default', '<version>(/<directory>)/<controller>(.<format>)',
		array(
			'version' => 'v1',
			'format'  => '(json|xml|html)',
		))
		->defaults(array(
			'format' => 'html',
		));
		
### :clipboard: Controllers

Each REST controller in your app must extend `Controller_REST`. Your controller will then have access to the following variables:

* `$this->_params` - an associated array with all the parameters passed in the request, no matter which method was used.

The following action functions can be implemented to support each one of the corresponding HTTP methods:

* `action_index()` - for GET requests.
* `action_create()` - for POST requests.
* `action_update()` - for PUT requests.
* `action_delete()` - for DELETE requests.

### :memo: Models

You can use any model class you want.

### :newspaper: Views

By default, the output format is HTML, the module searches for a relevant 
View file using the same directory structure as the request. For example, 
is the request was for `/path/to/object.html`, then the module searches for the 
View file `/path/to/object.php`.

If your Controller is`classes/Controller/Welcome.php`
the module auto detects if your View is one for all methods `views/welcome.php` or
a different one for each method like `views/welcome/{index/create/update/delete}.php`

All the data that would usually return in a JSON format, is available for the View file in the variable `$data`.

The output formats JSON and XML don't require any special views.

## :paperclip: Special Parameters

The following special query parameters are supported:

* `suppressResponseCodes` - some clients cannot handle HTTP responses different than 200. Passing `suppressResponseCodes=true` will make the response always return `200 OK`, while attaching the real response code as an extra key in the response body. More information here: <https://blog.apigee.com/detail/restful_api_design_tips_for_handling_exceptional_behavior>
* `method` - some clients cannot set an HTTP method different than GET. For these clients, we support simply passing the method as a query parameter. `method` can simply be set to POST, PUT, DELETE or any other method you'd like to support.
* `attachement` - you may sometimes like to allow your users to query your API directly from their browser with a direct link to download the data. For these occasions you may add this parameter with a value representing a file name. This will make the module declare a "content-disposition" header that'll make the user's browser open a download window.

## :computer: Command Line

You may create requests to your REST API using CLI commands. The following parameters are expected:

* `headers` - the request's headers.
* `method` - the request's method (GET, POST etc.).
* `get` - the GET query parameters.
* `post` - the POST parameters.
* `resource` - the resource, usually represented by a URL, to which the request should be sent.

## :fire: TODO


* API Authentication (maybe with OAUTH 2 Support).

## :thumbsup: Special Thanks

Thanks a lot to [ozadi3](https://github.com/ozadi3), I couldn't have this without you!

The module is maintained by [Supersonic](http://www.supersonic.com).

## :clap: Contributing

As usual, [fork and send pull requests](https://help.github.com/articles/fork-a-repo)

## :beginner: Getting Help

* Open issues in this project.