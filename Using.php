<?

include('Logger.php');

Logger::$label = "MYPROJECT\t";


Logger::timer(LOG_INFO, "START");
Logger::warning("My message");
Logger::timer(LOG_INFO, "END");

// --
