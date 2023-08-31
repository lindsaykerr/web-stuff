<?php
/**
 * This script functions as a dynamic IP logger. Whenever a valid request is made to this script,
 * the ip and port of the service is logged to a database. 
 * 
 * @package DynIp
 * @version 0.1.0
 * @since 0.1.0
 * @author Lindsay Kerr 
 * @license http://opensource.org/licenses/MIT MIT License
 * @copyright Lindsay Kerr, 2023
 */

require_once 'error_logging.php';

/**
 * Logger class
 * 
 * This class is responsible for logging the ip and port of a service
 * to a database
 */
class Logger {
    // database credentials
    private string $db_host;
    private string $db_username;
    private string $db_password;
    private string $db;

    private mysqli $connection;

    // service details
    private string $serv;
    private string $serv_ip;
    private string $serv_port;
    private string $serv_code;
    
    /**
     * Constructor
     * 
     * @param string $db_host the database host
     * @param string $db_username the database username
     * @param string $db_password the database password
     * @param string $database the database name
     * @param string $service_type the service type
     * 
     */
    public function __construct(
        $db_host='localhost',
        $db_username='admin',
        $db_password='password',
        $database='db',
        $service_type='loc1'
        
    ) {
        $this->db_host = $db_host;
        $this->db_username = $db_username;
        $this->db_password = $db_password;
        $this->db = $database;
   
        $this->serv = $service_type;
        $this->serv_ip = $this->set_server_ip();
        $this->serv_port = $this->set_server_port();
        $this->serv_code = isset($_POST['code']) ? $_POST['code'] : ''; 
    }

    /**
     * Log the ip and port of the service to the database
     */
    public function log() {  

        if(!$this->verify_code()) {
            return;
        }

        if ($this->connect_to_db()) {
            $this->update_service_info();
            $this->connection->close();
        }
    }

    /**
     * Verify the code sent by the service
     * 
     * @todo implement a more secure method of verifying the code
     * @return bool true if the code is valid, false otherwise
     */
    private function verify_code() {
        
        if ($this->serv_code != 'CN_1268') {
            log_err("Logger - Code not valid");
            header("HTTP/1.1 403 Forbidden");
            echo "403 - Forbidden";
            return false;
        }
        return true;
    }


    /**
     * Connect to the database
     * 
     * @return bool true if the connection was successful, false otherwise
     */
    private function connect_to_db() {
        
        $this->connection = new mysqli($this->db_host, $this->db_username, $this->db_password, $this->db);
        if ($this->connection->connect_errno) {
            log_err("Logger - Failed to connect to database, login credentials likely invalid");
            header("HTTP/1.1 500 Internal Server Error");
            echo "500 - Internal Server Error : failed to connect to storage";
            return false;
        }
        return true;
        
    }

    /**
     * Update the ip, port and status of the service on the database
     */
    private function update_service_info() {
     
        $statement = $this->connection->prepare(
            "UPDATE hosts 
            SET hosts.ip=(?), hosts.status=1, hosts.port=(?) 
            WHERE hosts.id ='$this->serv'"
            );
        
        $statement->bind_param('si',$this->serv_ip, $this->serv_port);
        
        if(!$statement->execute()) {
            log_err("Logger - Failed to update IP, possible issue with database or query");
            header("HTTP/1.1 500 Internal Server Error");
            echo "500 - Internal Server Error : failed to query storage";
        } else {
            header("HTTP/1.1 200 OK");
            echo "200 - OK";
        } 
        $statement->close();
    }

    /**
     * Will set the ip of the remote server, 
     * if the request was from a proxy then the forwarded ip will be used
     * otherwise the remote address will be used
     * 
     * @return string the ip of the remote server
     */
    private function set_server_ip(): string {

        // if the request was from a proxy use the forwarded ip
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        // otherwise use remote address
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? '' ;
        }
    }

    /**
     * Will set the port of the remote server, 
     * if the request was from a proxy then the forwarded port will be used
     * otherwise the remote port will be used
     * 
     * @return int the port of the remote server
     */
    private function set_server_port() {

        // if the request was from a proxy use the forwarded port
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            return $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' ? 443 : 80;
        
        // if a custom port was used, use  that
        } else if (isset($_POST['port'])) {
            $result = $this->validate_port($_POST['port']);
            if ($result) {
                return $result;
            }
            else {
                return 80;
            }

        // otherwise use remote port
        } else {
            return (int) $_SERVER['REMOTE_PORT'];
        }
    }

    /**
     * Validate the port
     * 
     * @param string $port the port to validate
     * @return int|false the port if it is valid, false otherwise
     */
    private function validate_port($port) { 
            return filter_var($port, FILTER_VALIDATE_INT, array(
                "options" => array(
                    "min_range" => 1,
                    "max_range" => 65535
                )
            ));
               
    }

}



$logger = new Logger(
    db_username: 'dynip_user', 
    db_password: 'dynip_user001', 
    database:'dyn_ip'
);

$logger->log();
