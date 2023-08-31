<?php 
/**
 * The purpose of this script is to check the status of a service.
 * It provides the ServiceStatus of which there is static check function
 * that is used for this purpose.
 * 
 * 
 * @package DynIp
 * @version 0.1.0
 * @since 0.1.0
 * @author Lindsay Kerr 
 * @license http://opensource.org/licenses/MIT MIT License
 * @copyright Lindsay Kerr, 2023
 */


require_once 'error_logging.php';

// define the minimum time in minutes that a service can go without updating
// default should be about 15 minutes, a value of 1 can be used for testing .
define("ELAPSED_TIME_MIN", 1);

class ServiceStatus {
    private const USERNAME = 'dynip_user';
    private const PASSWORD = 'dynip_user001';
    private const DB_NAME = 'dyn_ip_v2';
    private const HOST = 'localhost';

    public static function check($service_name='loc1'): false|array {
        $mysqli = new mysqli(
            ServiceStatus::HOST, 
            ServiceStatus::USERNAME, 
            ServiceStatus::PASSWORD,
            ServiceStatus::DB_NAME
        );

        // failed to connect to database
        if ($mysqli->connect_error) {
            log_err('Error : ('. $mysqli->connect_errno .') '. $mysqli->connect_error);

            return false;
        }

        // query the database for the ip, port and status of the service
        $query = "SELECT ip, status, port, datetime FROM hosts WHERE hosts.id ='$service_name'";
        $result = $mysqli->query($query);


        // if the query fails, then return an error, otherwise check the status of the service
        if (!is_bool($result)) {
          
            $row = $result->fetch_assoc();
            $result->free();            
            
            $ip = $row['ip'];
            $port = $row['port'];
            $timestamp = $row['datetime'];
            
            // if the timestamp record is older than 1 minute
            // then the service has not been updated. return an error
            if ((time() - ELAPSED_TIME_MIN*60) > (int) $timestamp) {
                return false;
            }
            // otherwise, the service is active and has been updated recently, so return the ip and port
            else {
                return array(
                    'ip' => $ip,
                    'port' => $port
                );
            }
    
        } else {
            log_err("Checker - could not execute query to retrieve dyn ip and port from db");
        } 

        $mysqli->close();

        return false;
    }
} 








