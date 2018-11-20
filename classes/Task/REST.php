<?php
/**
 * An implementation of the Minion_Task interface,
 * to allow REST API requests from CLI.
 *
 * @package    REST
 * @category   Minion
 * @copyright  (c) 2007-2014  Adi Oz
 * @copyright  (c) since 2018 Koseven Team
 * @license    https://koseven.ga/LICENSE.md
 */
class Kohana_Task_REST extends Minion_Task {

    /**
     * The list of options this task accepts and their default values.
     * @var array
     */
    protected $_options = array
    (
        'get' => NULL,
        'resource' => NULL,
        'headers' => NULL,
        'method' => NULL,
        'post' => NULL,
    );

    /**
     * This is an execute task for REST.
     * @param array $params
     * @throws Request_Exception
     */
    protected function _execute(array $params)
    {
        if (isset($params['headers']))
        {
            // Save the headers in $_SERVER
            if (($headers = json_decode($params['headers'], true)) !== NULL)
            {
                foreach ($headers as $name => $value)
                {
                    $_SERVER['HTTP_'. strtoupper($name)] = (string) $value;
                }
            }
            // Remove the headers before execute the request.
            unset($params['headers']);
        }
        if (isset($params['method']))
        {
            // Use the specified method.
            $method = strtoupper($params['method']);
        }
        else
        {
            $method = 'GET';
        }
        if (isset($params['get']))
        {
            // Overload the global GET data.
            parse_str($params['get'], $_GET);
        }
        if (isset($params['post']))
        {
            // Overload the global POST data.
            parse_str($params['post'], $_POST);
        }
        print Request::factory($params['resource'])
            ->method($method)
            ->execute();
    }

    /**
     * Adds validation rules/labels for validating _options
     * @param Validation $validation   The validation object to add rules to
     * @return Validation
     */
    public function build_validation(Validation $validation) : Validation
    {
        return parent::build_validation($validation)
            ->rule('headers', 'not_empty')
            ->rule('resource', 'not_empty');
    }
}