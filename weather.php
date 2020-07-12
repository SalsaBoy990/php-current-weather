<?php
@header( 'Content-Type: application/json' );

/**
 * Class made to access the Open Weather API
 * @see https://openweathermap.org/current
 * validate user input, call the API,
 * Author: András Gulácsi 2020
 */
class CurrentWeather
{
  // Open Weather API key (free version)
  // Get your key: https://openweathermap.org/api
  private const APIKEY = 'YOUR_API_KEY_HERE';


  // City name
  private $cityName;
  // Language
  public static $lang = "hu";
  // Country code
  public static $countryCode = "hu";


  //  For temperature in Fahrenheit use units=imperial
  // For temperature in Celsius use units=metric
  // Temperature in Kelvin is used by default, no need to use units parameter in API call
  // Measurements unit (metric, imperial)
  public static $units = "metric";


  // Time of weather report
  private $time;
  // Lat
  private $latitude;
  // Lon
  private $longitude;
  // create map link
  private $mapLink;

  public $errorMessage;


  // Short description
  private $weatherDescription;
  // store weather image
  private $weatherImage;
  // Air temperature
  private $temperature;
  // Relative humidity
  private $humidity;
  // wind speed
  private $windSpeed;
  // wind direction
  private $windDirection;
  // precipitation amount in last hour
  private $precipitation1h;





  // CONSTRUCTOR ------------------------------
  // inizialize properties, some defaults added
  function __construct(
    $cityName = null,
    $time = null,
    $weatherDescription = "",
    $temperature = null,
    $humidity = null,
    $windSpeed = null,
    $windDirection = "",
    $precipitation1h = null
  ) {
    // Initialize
    $this->cityName = $cityName;
    $this->time = $time;
    $this->weatherDescription = $weatherDescription;
    $this->temperature = $temperature;
    $this->humidity = $humidity;
    $this->windSpeed = $windSpeed;
    $this->windDirection = $windDirection;
    $this->precipitation1h = $precipitation1h;


    $this->weatherImage = "";
    $this->latitude = null;
    $this->longitude = null;
    $this->mapLink = null;
    $this->errorMessage = null;
  }


  // DESCTRUCTOR -------------------------------
  function __destruct()
  {
  }

  // setter
  private function setCityName($cityName)
  {
    $this->cityName = $cityName;
  }
  // getter
  public function getCityName()
  {
    return $this->cityName;
  }

  // some basic cleaning to remove whitespace, tags, slashes
  private function preCleaningInput($arg)
  {
    $arg = trim($arg);
    $arg = strip_tags($arg);
    $arg = stripslashes($arg);
    return $arg;
  }


  // Validate user input
  public function validateInputForm()
  {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

      if (
        isset($_POST['city']) &&
        !empty($_POST['city']) &&
        $_POST['city'] === $this->preCleaningInput($_POST['city']) &&
        preg_match("/^[a-zA-ZáéíóöőúüűÁÉÍÓÖŐÚÜŰ\s]*$/", $_POST['city'])
      ) {
        $this->setCityName($_POST['city']);
        return true;
      } else {
        return false;
      }
    }
  }


  // print all stored properties of class
  public function echoAllProperties()
  {
    echo '<h2>City: ' .  $this->cityName . '</h2>';
    echo '<span>Country code: ' . self::$countryCode . '</span><br />';
    echo '<span>Language: ' . self::$lang . '</span><br />';
    echo '<span>Time of measurement: ' . $this->time . '</span><br />';
    echo '<span>Measurement units:' . self::$units . '</span><br />';
    echo '<span>Weather: ' . $this->weatherDescription . '</span><br />';
    echo '<span>Weather image: ' . $this->weatherImage . '</span><br />';
    echo '<span>Temperature: ' . $this->temperature . '</span><br />';
    echo '<span>Humidity: ' . $this->humidity . '</span><br />';
    echo '<span>Wind speed: ' . $this->windSpeed . '</span><br />';
    echo '<span>wind direction: ' . $this->windDirection . '</span><br />';
    echo '<span>Previous hour\'s precipitation: ' . $this->precipitation1h . '</span><br />';
  }


  public function getWeatherDataFromApi()
  {
    $lang = urlencode(self::$lang);
    $countryCode = urlencode(self::$countryCode);
    $cityName = $this->getCityName();
    $units = self::$units;
    $apiKey = self::APIKEY;

    $currentWeatherUrlCityName = "http://api.openweathermap.org/data/2.5/weather?q={$cityName},{$countryCode}&appid={$apiKey}&lang={$lang}&units={$units}";


    // Report all errors except E_NOTICE
    error_reporting(E_ALL & ~E_WARNING);
    try {

      // change to the free URL if you're using the free version
      $json = file_get_contents($currentWeatherUrlCityName);

      // print_r($json);

      if ($json === false) {
        throw new Exception('Http request failed. Wrong city name! Also check your API key if you did not set.');
      }
    } catch (Exception $e) {
      $this->errorMessage = $e->getMessage();
      ;
      echo json_encode(array(
        'weatherData' => '',
        'errorMessage' => $this->errorMessage
      )
    );
    }
    // report all errors
    error_reporting(E_ALL);

    // var_dump($http_response_header);
    $response = json_decode($json, true);
    // echo '<pre>';
    // print_r($response);
    // echo '</pre>';
    return $response;
  }



  public function storeWeatherData($res)
  {
    // correct UTC time with timezone
    $timestamp= intval($res['dt'], 10);
    // get time hour:minutes
    // get date and local time
    $this->time = date('H:m', $timestamp);


    // get latitude and longitude
    if ($res['coord']['lat']) {
      $this->latitude = $res['coord']['lat'];
    }
    if ($res['coord']['lon']) {
      $this->longitude = $res['coord']['lon'];
    }

    // create map link
    if ($this->longitude && $this->latitude) {
      $this->mapLink = "https://openweathermap.org/weathermap?zoom=12&lat={$this->latitude}&lon={$this->longitude}";
    }

    // get temperature data
    if ($res['main']['temp']) {
      $this->temperature = floatval($res['main']['temp']);
    }

    // get humidity in percent
    if ($res['main']['humidity']) {
      $this->humidity = $res['main']['humidity'];
    }

    // get precipitation amount for the previous 1 hour
    if (isset($res['rain']['1h'])) {
      $this->precipitation1h = intval($res['rain']['1h']);
    } else {
      $this->precipitation1h = 0;
    }

    // get windspeed
    if ($res['wind']['speed']) {
      $this->windSpeed = floatval($res['wind']['speed']) * 3.6; // km/h
    }


    // get wind direction
    if ($res['wind']['deg']) {
      $this->windDirection = intval($res['wind']['deg'], 10);
    }

    // get short weather description
    if ($res['weather'][0]['description']) {
      $this->weatherDescription = $res['weather'][0]['description'];
    }

    // get weather state id
    if ($res['weather'][0]['id']) {
      $weatherIconId = $res['weather'][0]['id'];
      $weatherIconId = intval($weatherIconId, 10);
    }

    // get weather icon img sources from open weather
    $this->getWeatherIconImage($weatherIconId);

    // get wind direction in cardinal diections 
    $this->getWindDirection();
  }

  private function getWeatherIconImage($weatherIconId)
  {
    $weatherImg = '';
    switch ($weatherIconId) {
        // Thunderstorm
      case 200:
      case 201:
      case 202:
      case 210:
      case 211:
      case 212:
      case 221:
      case 230:
      case 231:
        $weatherImg = '<img class="icon" src="images/weather/11d.png" alt="' . $this->weatherDescription . '" />';
        break;


        // Drizzle
      case 300:
      case 301:
      case 302:
      case 310:
      case 311:
      case 312:
      case 313:
      case 314:
      case 321:
        $weatherImg = '<img class="icon" src="images/weather/09d.png" alt="' . $this->weatherDescription . '" />';
        break;


        // Rain
      case 500:
      case 501:
      case 502:
      case 503:
      case 504:
        $weatherImg = '<img class="icon" src="images/weather/10d.png" alt="' . $this->weatherDescription . '" />';
        break;

        // Rain freezing rain
      case 511:
        $weatherImg = '<img class="icon" src="images/weather/13d.png" alt="' . $this->weatherDescription . '" />';
        break;

        // Rain  
      case 520:
      case 521:
      case 522:
      case 531:
        $weatherImg = '<img class="icon" src="images/weather/09d.png" alt="' . $this->weatherDescription . '" />';
        break;


        // Snow
      case 600:
      case 601:
      case 602:
      case 611:
      case 612:
      case 613:
      case 615:
      case 616:
      case 620:
      case 621:
      case 622:
        $weatherImg = '<img class="icon" src="images/weather/13d.png" alt="' . $this->weatherDescription . '" />';
        break;


        // Atmosphere
      case 701:
      case 711:
      case 721:
      case 731:
      case 741:
      case 751:
      case 761:
      case 762:
      case 771:
      case 781:
        $weatherImg = '<img class="icon" src="images/weather/50d.png" alt="' . $this->weatherDescription . '" />';
        break;


        // Clear
      case 800:
        // 01n ?
        $weatherImg = '<img class="icon" src="images/weather/01d.png" alt="' . $this->weatherDescription . '" />';
        break;


        // Clouds
      case 801:
        $weatherImg = '<img class="icon" src="images/weather/02d.png" alt="' . $this->weatherDescription . '" />';
        break;
      case 802:
        $weatherImg = '<img class="icon" src="images/weather/03d.png" alt="' . $this->weatherDescription . '" />';
        break;
      case 803:
        $weatherImg = '<img class="icon" src="images/weather/04d.png" alt="' . $this->weatherDescription . '" />';
        break;
      case 804:
        $weatherImg = '<img class="icon" src="images/weather/04d.png" alt="' . $this->weatherDescription . '" />';
        break;

      default:
        $weatherImg = '';
    }
    $this->weatherImage = $weatherImg;
  }

  private function getWindDirection()
  {
    $tmp = $this->windDirection;
    // echo $tmp . '°<br />';
    if ($tmp >= 349 && $tmp < 11) {
      $tmp = "É";
    } else if ($tmp >= 11 && $tmp < 34) {
      $tmp = "ÉÉK";
    } else if ($tmp >= 34 && $tmp < 56) {
      $tmp = "ÉK";
    } else if ($tmp >= 56 && $tmp < 79) {
      $tmp = "KÉK";
    } else if ($tmp >= 79 && $tmp < 101) {
      $tmp = "K";
    } else if ($tmp >= 101 && $tmp < 124) {
      $tmp = "KDK";
    } else if ($tmp >= 124 && $tmp < 146) {
      $tmp = "DK";
    } else if ($tmp >= 146 && $tmp < 169) {
      $tmp = "DDK";
    } else if ($tmp >= 169 && $tmp < 191) {
      $tmp = "D";
    } else if ($tmp >= 191 && $tmp < 214) {
      $tmp = "DDNY";
    } else if ($tmp >= 214 && $tmp < 236) {
      $tmp = "DNY";
    } else if ($tmp >= 236 && $tmp < 259) {
      $tmp = "NYDNY";
    } else if ($tmp >= 259 && $tmp < 281) {
      $tmp = "NY";
    } else if ($tmp >= 281 && $tmp < 304) {
      $tmp = "NYÉNY";
    } else if ($tmp >= 304 && $tmp < 326) {
      $tmp = "ÉNY";
    } else if ($tmp >= 326 && $tmp < 349) {
      $tmp = "ÉÉNY";
    }
    $this->windDirection = $tmp;
  }


  public function injectDataToHtml()
  {
    $html = '<h2>' .  $this->cityName . ' időjárása</h2>';
    $html .= '<ul class="no-bullets">';
    $html .= '<li>' . $this->time . '</li>';
    $html .= '<li style="display: inline-block;">' . $this->weatherImage . $this->weatherDescription . '</li>';

    $html .= '<li>hőmérséklet: ' . $this->temperature . ' °C</li>';
    $html .= '<li>páratartalom: ' . $this->humidity . '%</li>';
    $html .= '<li>szélsebesség: ' . round($this->windSpeed) . ' km/h</li>';
    $html .= '<li>szélirány: ' . $this->windDirection . '</li>';
    $html .= '<li><a href="' . $this->mapLink . '">Időjárástérkép</a></li>';
    $html .= '</ul>';
    echo json_encode(array(
        'weatherData' => $html,
        'errorMessage' => $this->errorMessage
      )
    );
  }
}

// CurrentWeather::$lang = 'en';
// CurrentWeather::$units = 'imperial';


$myWeather = new CurrentWeather();

// it will store the response from the api
$weatherData = null;
if ($myWeather->validateInputForm()) {
  $response = $myWeather->getWeatherDataFromApi();
  if ($response) {
    $myWeather->storeWeatherData($response);
    $weatherData = $myWeather->injectDataToHtml();
  }
}
