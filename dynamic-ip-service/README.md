# A simple dynamic IP service

This is a simple dynamic IP service allows a website to connect to a service running over a dynamic IP address.

## Why?

There may be times when I want to run a temporary service on my local network, as a means to demonstrate a concept 
or to test something. This service allows me to run a service on my local network, and expose it to the internet 
through a dynamic IP address.

## How?

The solution address the problem of a changing IP address in a similar way to how dynamic DNS services work. There
is a client that runs on the local network, and a target server that users use which has a static IP 
address or domain name. The ip client periodically checks in on the target server. The target server records the 
IP address. When users access the target server, a script running on that server will query the ip record to check 
if the service is still active or not.

## There are two solutions

### Solution 1 

Was an initial attempt at the problem and uses what I call a passive client. This is convoluted solution, but it works. 
The client checks in with the target server once and then it is the responsibility of the target server to 
periodically check if the client is still active. If the client is not active, then the target server will mark the
service as inactive.This solution utilises an active/inactive flag on the database. I would not recommend this solution
over solution 2.

### Solution 2

This is a much simpler solution and more robust. It uses an active client. The client will periodically check-in to 
target server. When it does the target server will record the IP address of the client and a date-time stamp. When a user 
accesses the target server, a script will check if the service is still active before processing the user request. It does
so by checking the time difference between the last time the client checked in and the current time. If the time difference
is greater than a certain threshold, then the service is considered inactive.


## The limitations

The solution is not perfect. There are a few limitations:

1. There is no domain name support for the dynamic ip service. The target server will likely have domain. Therefor consideration
should be given to how users access the service through the target server. One way of doing this it to use JavaScript to 
fetch data from the service. This will work if the service is a REST API. If the service provides web server rendered content, 
then potentially a person could use an iframe to embed the service into the website. This is not ideal, but it is a solution.

2. Secure connections are not supported. This may or may not be an issue. If the service provides non-sensitive data, then
this may not be an issue, otherwise there is. The solution could use a proxy to provide a secure connection when requesting 
data from the target server to the service. It could do so by using self-signed certificates. So this may be doable.

 
