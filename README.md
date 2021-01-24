# Temperature-Week-Monitoring

This application performs relevant data concerning the temperature of a weather station of a Netatmo product during a week frame.

## Configuration

Before executing this script you should configure it with your application parameters concerning the client_id and client_secret variables of your Netatmo application.
You can also change the device_id and module_id if you want to change your station or your module. The monitoring frame can be modified and should be expressed in seconds.
The redirect_uri variable refers to the URL callback of your Netatmo application.

## Execution

During the execution of the script, an authentication process will start through the OAuth 2.0 protocol and you will be redirected to the Netatmo authentication page where the requested scopes are displayed.
In our case we only need the read_station scope in order to retrieve the data temperature of a station.
The result will be displayed with the name of the devices corresponding to the result of the calculation.

	70:ee:50:3f:13:36/02:00:00:3f:0a:54 :
	Minimum: -0.1
	Maximum: 12.7
	Mean: 6.5
	70:ee:50:14:53:38/02:00:00:14:43:f6 :
	Minimum: 3.9
	Maximum: 13.1
	Mean: 8.2 

## Details
Please read https://dev.netatmo.com/apidocumentation for further information.
