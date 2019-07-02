<?php
/**
 * Abstract Controller class for REST controller mapping.
 * Supports GET, PUT, POST, and DELETE.
 *
 * GET
 * :  Mapped to the "index" action
 *
 * POST
 * :  Mapped to the "create" action
 *
 * PUT
 * :  Mapped to the "update" action
 *
 * DELETE
 * :  Mapped to the "delete" action
 *
 * @package    REST
 * @category   Controller
 * @copyright  (c) 2007-2014  Kohana Team, Alon Pe'er, Adi Oz
 * @copyright  (c) since 2018 Koseven Team
 * @license    https://koseven.ga/LICENSE.md
 */
abstract class KO7_Controller_REST extends Controller {

    /**
     * REST types
     * @var array
     */
    protected $_action_map = array
    (
        HTTP_Request::GET    => 'index',
        HTTP_Request::PUT    => 'update',
        HTTP_Request::POST   => 'create',
        HTTP_Request::DELETE => 'delete',
    );

    /**
     * The request's parameters.
     * @var array
     */
    protected $_params;

    /**
     * Should non-200 response codes be suppressed.
     * @see https://blog.apigee.com/detail/restful_api_design_tips_for_handling_exceptional_behavior
     * @var boolean
     */
    protected $_suppress_response_codes;

    /**
     * The output format to be used (JSON, XML etc.).
     * @var string
     */
    public $output_format;

    /**
     * Checks the requested method against the available methods. If the method
     * is supported, sets the request action from the map. If not supported,
     * and an alternative action wasn't set, the "invalid" action will be called.
     */
    public function before()
    {
        $this->_overwrite_method();
        $method = $this->request->method();

        if (! isset($this->_action_map[$method]))
        {
            $this->request->action('invalid');
        }
        else
        {
            $this->request->action($this->_action_map[$method]);
        }

        $this->_init_params();

        // Get output format from route file extension.
        $this->output_format = $this->request->param('format');

        // Set response code suppressing.
        $this->_suppress_response_codes = isset($this->_params['suppressResponseCodes']) && $this->_params['suppressResponseCodes'] === 'true';
    }

    /**
     * Adds a cache control header.
     */
    public function after()
    {
        if (in_array($this->request->method(), array
        (
            HTTP_Request::PUT,
            HTTP_Request::POST,
            HTTP_Request::DELETE
        )))
        {
            $this->response->headers('cache-control', 'no-cache, no-store, max-age=0, must-revalidate');
        }
    }

    /**
     * Sends a 405 "Method Not Allowed" response and a list of allowed actions.
     * @throws KO7_Exception
     */
    public function action_invalid()
    {
        $this->response->status(405)
            ->headers('Allow', implode(', ', array_keys($this->_action_map)));
    }

    /**
     * Handling of output data set in action methods with $this->rest_output($data, $code).
     * @param array $data           Response Data
     * @param int $code             Response Code
     * @throws KO7_Exception
     */
    protected function rest_output(array $data = array(), int $code = 200)
    {
        // Handle an empty and valid response.
        if (empty($data) && $code === 200)
        {
            $data = array
            (
                'code'  => 404,
                'error' => 'No records found',
            );
            $code = 404;
        }
        if ($this->_suppress_response_codes)
        {
            $this->response->status(200);
            $data['responseCode'] = $code;
        }
        else
        {
            $this->response->status($code);
        }
        $format_method = '_format_' . $this->output_format;
        // If the format method exists, call and return the output in that format
        if (method_exists($this, $format_method))
        {
            $output_data = $this->$format_method($data);
            $this->response->headers('content-type', File::mime_by_ext($this->output_format));
            $this->response->headers('content-length', (string) strlen($output_data));

            // Support attachment header
            if (isset($this->_params['attachment']) && Valid::regex($this->_params['attachment'], '/^[-\pL\pN_, ]++$/uD'))
            {
                $this->response->headers('content-disposition', 'attachment; filename='. $this->_params['attachment'] .'.'. $this->output_format);
            }
            $this->response->body($output_data);
        }
        else
        {
            // Report an error.
            $this->response->status(500);
            throw new KO7_Exception('Unknown format method requested');
        }
    }

    /**
     * Format the output data to JSON.
     * @param array $data           Response Data
     * @return false|string         Encoded JSON string
     */
    private function _format_json(array $data = array())
    {
        return json_encode($data);
    }

    /**
     * Format the output data to XML with php-xml
     * @param array $data           Response Data
     * @return string               XML
     * @throws KO7_Exception
     */
    private function _format_xml(array $data = array()) : string
    {
        if (!extension_loaded('xml')) {
            throw new KO7_Exception('PHP XML Module not loaded');
        }
        $xml = new SimpleXMLElement('<root/>');
        $xml = $xml->addChild('data');
        array_walk_recursive($data, function($value, $key) use ($xml, &$result) {
            $result = $xml->addChild($key, $value);
        });
        return $xml->asXML();
    }

    /**
     * Call a View to format the data as HTML.
     * @param array $data           Response Data
     * @return string               HTML (if non existent, empty string)
     * @throws KO7_Exception
     */
    private function _format_html(array $data = array()) : string
    {
        // Support a fallback View for errors.
        if (isset($data['error']))
        {
            $data['responseCode'] = $this->response->status();
            $view_name = 'error';
        }
        else
        {
            $directory = strtolower($this->request->directory());
            if ($directory)
            {
                $directory .= DIRECTORY_SEPARATOR;
            }
            $view_name = $directory.strtolower($this->request->controller());
        }

        // e.G. Controller: Welcome, check views/Welcome.php else views/welcome/{index,update,create,delete}.php
        if (KO7::find_file('views', $view_name) === FALSE) {
            $view_name .= DIRECTORY_SEPARATOR .$this->request->action();
        }
        try
        {
            return View::factory($view_name, array('data' => $data))->render();
        }
        catch (View_Exception $e) {
            // Fall back to an empty string.
            // This way we don't have to satisfy *all* API requests as HTML.
            return '';
        }
    }

    /**
     * Implements support for setting the request method via a GET parameter.
     * @see https://blog.apigee.com/detail/restful_api_design_tips_for_handling_exceptional_behavior
     */
    private function _overwrite_method()
    {
        if ($this->request->method() === HTTP_Request::GET && ($method = $this->request->query('method')))
        {
            switch (strtoupper($method))
            {
                case HTTP_Request::POST:
                case HTTP_Request::PUT:
                case HTTP_Request::DELETE:
                    $this->request->method($method);
                    break;
                default:
                    break;
            }
        }
        else
        {
            // Try fetching method from HTTP_X_HTTP_METHOD_OVERRIDE before falling back on the detected method.
            $this->request->method(Arr::get($_SERVER, 'HTTP_X_HTTP_METHOD_OVERRIDE', $this->request->method()));
        }
    }

    /**
     * Initializes the request params array based on the current request.
     */
    private function _init_params()
    {
        $this->_params = array();
        switch ($this->request->method())
        {
            case HTTP_Request::POST:
            case HTTP_Request::PUT:
            case HTTP_Request::DELETE:
                if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== FALSE)
                {
                    $parsed_body = json_decode($this->request->body(), true);
                }
                else
                {
                    parse_str($this->request->body(), $parsed_body);
                }
                $this->_params = array_merge((array) $parsed_body, (array) $this->request->post());
                // No break because all methods should support query parameters by default.
            case HTTP_Request::GET:
                $this->_params = array_merge((array) $this->request->query(), $this->_params);
                break;

            default:
                break;
        }
    }
}
