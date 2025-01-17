<?php

/**
 * Assistant.php is the model for an AI Assistant that uses OpenAI GPT
 * @copyright 2023
 * @author Dory A.Azar
 * @version 1.0
 */

/**
 * The Assistant class handles all exchanges with the OpenAI Assistant API
 * @author Dory A.Azar
 * @version 1.0
 */

namespace Caligrafy;
use \Exception as Exception;

define("ASSISTANT_GPT3", "gpt-3.5-turbo");
define ("ASSISTANT_GPT4", "gpt-4");
define ("ASSISTANT_GPT4O", "gpt-4o");
define ("ASSISTANT_GPT4OMINI", "gpt-4o mini");
define ("ASSISTANT_GPT4_32", "gpt-4-32k");
define ("ASSISTANT_GPT4_LATEST", "gpt-4o");
define ("ASSISTANT_GPT4_LATEST_VISION", "gpt-4-vision-preview");



class Assistant extends \stdClass {

    /**
	* @var string the OpenAI API Endpoint
	* @property OpenAI API URI
	*/
	private $_openai_url = "https://api.openai.com/v1/assistants";


    /**
	* @var string the OpenAI API Endpoint
	* @property OpenAI API URI
	*/
	private $_assistant_file_url = "https://api.openai.com/v1/files";

    
    /**
	* @var string the OpenAI API Endpoint
	* @property OpenAI API URI
	*/
	private $_thread_url = "https://api.openai.com/v1/threads";



    /**
	* @var string the OpenAI Assistant Model
	* @property model OpenAI Assistant Model
	*/
	private $_model = ASSISTANT_GPT4_LATEST;


    /**
	* @var string the OpenAI request headers
	* @property headers OpenAI request headers
	*/
	private $_headers = array("Authorization: Bearer ".OPEN_AI_KEY,
                              "OpenAI-Beta: assistants=v2");

    /**
	* @var string the OpenAI Assistant Name
	* @property name OpenAI Assistant Name
	*/
	public $name;


    /**
	* @var string the OpenAI Assistant Description
	* @property description OpenAI Assistant Description
	*/
	public $description;


    /**
	* @var string the OpenAI Assistant Instructions
	* @property instructions OpenAI Assistant Instructions
	*/
	public $instructions;


    /**
	* @var array the OpenAI Assistant Tools
	* @property tools OpenAI Assistant Tools
	*/
	public $tools = array(["type" => "file_search"]);

    /**
	* @var array the OpenAI Assistant Tools
	* @property tools OpenAI Assistant Tools
	*/
	public $tool_resources = array("code_interpreter" => ["file_ids" => [] ]);

    /**
	* @var array the OpenAI Assistant attached file ids
	* @property file_ids OpenAI Assistant attached file ids
	*/
	public $file_ids = [];

    /**
	* @var array User entered file paths array
	* @property file_paths User entered file paths array
	*/
	public $file_paths;


    /**
	* @var array the OpenAI Assistant Metadata
	* @property metadata OpenAI Assistant Metadata
	*/
	public $metadata;

    /**
	* @var string the OpenAI Assistant ID
	* @property assistant_id OpenAI Assistant ID
	*/
	public $assistant_id = '';

    /**
	* @var string the OpenAI Assistant ID
	* @property thread_id OpenAI Assistant ID
	*/
	public $thread_id = '';

    /**
	* @var string the thread run ID
	* @property run_id the thread run ID
	*/
	public $run_id = '';

    public $status = 'queued';

    /**
	* @var array thread messages
	* @property messages thread messages
	*/
	public $messages = [];



    /**
     * Create a new Assistant
     */
    public function __construct($parameters = array())
    {

        // Initialize variables
        $url = $this->_openai_url;
                
        // Preparing the headers
        $headers = array_merge($this->_headers, array(
            "Content-Type: application/json"
            )
        );

        if (!empty($parameters)) {

            // If there is an ID, change the $url and retrieve the assistant
            if (isset($parameters['assistant_id'])) {
                $this->assistant_id = $parameters['assistant_id'];
                $url = $this->_openai_url."/".$this->assistant_id;
                $this->retrieve();
            }
        
            $this->_model = $parameters['model']?? $this->_model;
            $this->tools = $parameters['tools']?? $this->tools;
            $this->name = $parameters['name']?? $this->name;
            $this->description = $parameters['description']?? $this->description;
            $this->instructions = $parameters['instructions']?? $this->instructions;
            $this->file_paths = $parameters['file_paths']?? $this->file_paths;
            $this->file_ids = isset($parameters['file_paths']) ? $this->uploadAssistantFiles($this->file_paths) : ($parameters['file_ids']?? $this->file_ids);
            $this->tool_resources = $this->file_ids? array("code_interpreter" => ["file_ids" => $this->file_ids ]) : $this->tool_resources;
            $this->metadata = $parameters['metadata']?? $this->metadata;
        }

        $body = array(
            "model" => $this->_model,
            "name" => $this->name?? '',
            "description" => $this->description?? '',
            "instructions" => $this->instructions?? '',
            "tools" => $this->tools?? [],
            "tool_resources" => $this->tool_resources,
            "metadata" => $this->metadata?? null
        );

        $response = httpRequest($url, 'POST', $body, $headers);
        $this->assistant_id = $response['id']?? $this->assistant_id;
        return $this;
        
    }

    /**
     * Retrieve existing Assistant
     */
    public function retrieve() {

        // Initializing response
        $assistant = [];
        
        // Preparing the headers
        $headers = array_merge($this->_headers, array(
            "Content-Type: application/json"
            )
        );


        if ($this->assistant_id)
            $assistant = httpRequest($this->_openai_url."/".$this->assistant_id, 'GET', array(), $headers);

        $this->assistant_id = $assistant['id']?? $this->assistant_id;
        $this->_model = $assistant['model']?? $this->_model;
        $this->tools = $assistant['tools']?? $this->tools;
        $this->name = $assistant['name']?? $this->name;
        $this->description = $assistant['description']?? $this->description;
        $this->instructions = $assistant['instructions']?? $this->instructions;
        $this->file_ids = $assistant['file_ids']?? $this->file_ids;
        $this->metadata = $assistant['metadata']?? $this->metadata;

        return $this;

    }

    /**
     * Delete Assistant
     */
    public function delete() {
        if ($this->assistant_id)  {
            $response = httpRequest($this->_openai_url.'/'.$this->assistant_id, 'DELETE', array(), $this->_headers);
            if ($response['deleted'])
                return true;
        }
        return $this;
    }

    /**
     * Start the contextual conversation (new thread)
     * There is no limit to the number of messages that can be added to the thread
     */
    public function start() {
        $response = httpRequest($this->_thread_url, 'POST', array(), $this->_headers);
        $this->thread_id = $response['id']?? null;
        return $this;
    }

    /**
     * 
     * Delete a contextual conversation
     */

    public function end() {
        if ($this->thread_id)  {
            $response = httpRequest($this->_thread_url.'/'.$this->thread_id, 'DELETE', array(), $this->_headers);
            $this->thread_id = $response['deleted']?? null;
        }
        return  $this;
    }


     /**
     * 
     * Create a user message in the conversation
     */
    public function createMessage($content, $file_ids = [], $metadata = null, $role = "user") {

        // Preparing the headers
        $headers = array_merge($this->_headers, array(
            "Content-Type: application/json"
            )
        );

        $body = array(
            "role" => $role,
            "content" => $content,
            "attachments" => [],
            "metadata" => $metadata
        );

        if ($this->thread_id) {
            $response = httpRequest($this->_thread_url.'/'.$this->thread_id.'/messages', 'POST', $body, $headers);
            if ($response['id']) array_push($this->messages, $response);
        }
        return $this;
    }

    /**
     * 
     * List all messages in the active thread
     */
    public function listMessages($additional_instructions = '', $instructions = '', $model = '', $tools = [], $metadata = []) {
        // list all messages within a thread
        return $this->thread_id ? httpRequest($this->_thread_url.'/'.$this->thread_id.'/messages', 'GET', array(), $this->_headers) : '';
    }


    /**
     * 
     * Run the assistant in the thread
     */

    public function run($additional_instructions = '', $instructions = null, $tools = [], $model = '', $metadata = null)
    {
        // Preparing the headers
        $headers = array_merge($this->_headers, array(
            "Content-Type: application/json"
            )
        );
        // Overrides per run
        $this->instructions = $instructions?? $this->instructions;

        $body = array(
            "assistant_id" => $this->assistant_id?? null,
            "model" => $model?? $this->_model,
            "additional_instructions" => $additional_instructions,
            "instructions" => $this->instructions,
            "tools" => $tools?? $this->tools,
            "metadata" => $metadata
        );

        if ($this->thread_id && $this->assistant_id) {
            
            $response = httpRequest($this->_thread_url.'/'.$this->thread_id.'/runs', 'POST', $body, $headers);
            $this->run_id = $response['id']?? '';
            $this->status = $response['status']?? $this->status;
        }
        return $this;
    }


    /**
     * 
     * Retrieve the assistant responses
     */
    public function retrieveResponse() 
    {
        $statuses = ["queued", "in_progress"];
        $response = [];

        if (!in_array($this->status, $statuses)){
            $run_messages = $this->listMessages();
            if($run_messages['data'][0]) array_push($this->messages, $run_messages['data'][0]);
            return $this;
        }
        else {
            sleep(2);
            $response = $this->thread_id && $this->run_id ? httpRequest($this->_thread_url.'/'.$this->thread_id.'/runs/'.$this->run_id, 'GET', array(), $this->_headers) : ''; 
            $this->status = $response['status'];
            return $this->retrieveResponse();
        }

    }


    /**
     * Uploading files for assistant usage
     * parameters:
     ** file_paths: array for file paths
     */
    public function uploadAssistantFiles($specified_paths = [])
    {
        // Identify what file path to consider
        $paths = $specified_paths?? $this->file_paths;

        // Initialize the output
        $output = [];

        // Initializing the headers
        $headers = array_merge($this->_headers, array(
            "Accept: application/json",
            "Content-Type: multipart/form-data")
        );

        foreach ($paths as $file) {
            $response = httpFile($this->_assistant_file_url, $file, $headers);
            if (isset($response['id'])) {

                // add the id to the output
                array_push($output, $response['id']);


                // If not upon creation, the files need to be attached to the assistant
                if ($specified_paths && $this->assistant_id) {

                    // Overriding the headers
                    $headers = array_merge($this->_headers, array(
                        "Content-Type: application/json"
                        )
                    );

                    $body = array(
                        "file_id" => $response['id']
                    );

                    // Attach to assistant
                    $attachResponse = httpRequest($this->_openai_url.'/'.$this->assistant_id.'/files', 'POST', $body, $headers);

                    // Update the assisstant object to reflect attached files
                    if (isset($attachReponse['id']))  {

                        // add the path to the assistant file paths
                        array_push($this->file_paths, $file);

                        // add the path to the assistant file paths
                        array_push($this->file_ids, $response['id']);
                    }
                }
            }

        }
        return $output;
    }

    /**
     * Delete uploaded files for assistant usage
     * parameters:
     ** file_id: id of the file
     */
    public function deleteAssistantFile($file_id) {
        $response = httpRequest($this->_assistant_file_url.'/'.$file_id, 'DELETE', array(), $this->_headers);
        return $response['deleted']?? false;
    }

}