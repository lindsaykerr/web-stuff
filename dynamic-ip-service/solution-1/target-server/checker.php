<?php
/**
 * This script is run every 5 minutes by a cron job. It checks if a service is still running and if not, 
 * it resets the ip and port to the default values.
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
 * Checker class
 */
class Checker {

    protected string $host;
    protected string $username;
    protected string $password;
    protected string $database;
    protected mysqli $connection;
    protected string $service;

    public function __construct($host='127.0.0.1', $username='admin', $password='password', $database='db', $service='loc1') {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->database = $database;
        $this->service = $service;
    }

    /**
     * Run the checker
     */
    public function run() {
        $this->connection = $this->connect();
        $this->check();
        $this->connection->close();
    }

    /**
     * Query the database for the ip and port of the service
     * 
     * @return array an array containing the ip, port and status of the service
     */
    private function query_status() {
        $result = $this->connection->query("SELECT hosts.ip as ip, hosts.port as port, hosts.status as status FROM hosts WHERE id = '$this->service'");
        if (!is_bool($result)) {
            $row = $result->fetch_assoc();
            $result->free();
            return array(
                $row['ip'], 
                $row['port'], 
                $row['status']
            );
        } else {
            log_err("Checker - could not execute query to retrieve dyn ip and port from db");
            return array(null, null, null);
        }
    }

    /**
     * Contact the service to see if it is still running,
     * if not, reset the ip and port to the default values
     */
    private function check() {
        list($ip, $port, $status) = $this->query_status();

        if ($ip == null || $port == null || $status == null) {
            log_err("Checker - could not retrieve dyn ip and port from db");
            return;
        }

        if ($status == 1) {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $ip);
            curl_setopt($ch, CURLOPT_PORT, $port);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, "");
            curl_setopt($ch, CURLOPT_TIMEOUT, 2);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: text/plain',
                'Connection: Close',
                )
            );
            $server_output = curl_exec($ch);
            curl_close($ch);


            // if the service returns anything other than OK, 
            // service is likely unavailable, reset the ip and port to the default values
            if (!$server_output || $server_output != "OK") {
                $result = $this->connection->execute_query("UPDATE hosts SET hosts.ip='127.0.0.1', hosts.status=0, hosts.port=80 WHERE hosts.id ='loc1'");
                if (!is_bool($result)) {
                    log_err("checker - could not update db after attempting to change status and reset ip");
                }
            } else {
                echo "Service is running\n";
            }

        }
        else {
            echo "Service is not running\n";
        }

               
    }

    /**
     * Connect to the database which holds service details
     * 
     * @return mysqli a mysqli object
     */
    private function connect() {
        $connection = new mysqli($this->host, $this->username, $this->password, $this->database);
        if ($connection->connect_error) {
            log_err("Checker - database connection failure ");
            $connection->close();
            exit;
        }
        return $connection;
    }
}


$checker = new Checker(
    username: 'dynip_user', 
    password: 'dynip_user001', 
    database:'dyn_ip'
);
$checker->run();