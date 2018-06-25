<?php
// OPTIONS - PLEASE CONFIGURE THESE BEFORE USE!

$yourEmail = "hola@eggtech.com.ar"; // the email address you wish to receive these mails through
$yourWebsite = "EggTech Programacion"; // the name of your website
$thanksPage = ''; // URL to 'thanks for sending mail' page; leave empty to keep message on the same page 
$maxPoints = 4; // max points a person can hit before it refuses to submit - recommend 4
$requiredFields = "name,email,comments"; // names of the fields you'd like to be required as a minimum, separate each field with a comma


// DO NOT EDIT BELOW HERE
$error_msg = array();
$result = null;

$requiredFields = explode(",", $requiredFields);

function clean($data) {
  $data = trim(stripslashes(strip_tags($data)));
  return $data;
}
function isBot() {
  $bots = array("Indy", "Blaiz", "Java", "libwww-perl", "Python", "OutfoxBot", "User-Agent", "PycURL", "AlphaServer", "T8Abot", "Syntryx", "WinHttp", "WebBandit", "nicebot", "Teoma", "alexa", "froogle", "inktomi", "looksmart", "URL_Spider_SQL", "Firefly", "NationalDirectory", "Ask Jeeves", "TECNOSEEK", "InfoSeek", "WebFindBot", "girafabot", "crawler", "www.galaxy.com", "Googlebot", "Scooter", "Slurp", "appie", "FAST", "WebBug", "Spade", "ZyBorg", "rabaz");

  foreach ($bots as $bot)
    if (stripos($_SERVER['HTTP_USER_AGENT'], $bot) !== false)
      return true;

  if (empty($_SERVER['HTTP_USER_AGENT']) || $_SERVER['HTTP_USER_AGENT'] == " ")
    return true;
  
  return false;
}

if ($_SERVER['REQUEST_METHOD'] == "POST") {
  if (isBot() !== false)
    $error_msg[] = "No bots please! UA reported as: ".$_SERVER['HTTP_USER_AGENT'];
    
  // lets check a few things - not enough to trigger an error on their own, but worth assigning a spam score.. 
  // score quickly adds up therefore allowing genuine users with 'accidental' score through but cutting out real spam :)
  $points = (int)0;
  
  $badwords = array("adult", "beastial", "bestial", "blowjob", "clit", "cum", "cunilingus", "cunillingus", "cunnilingus", "cunt", "ejaculate", "fag", "felatio", "fellatio", "fuck", "fuk", "fuks", "gangbang", "gangbanged", "gangbangs", "hotsex", "hardcode", "jism", "jiz", "orgasim", "orgasims", "orgasm", "orgasms", "phonesex", "phuk", "phuq", "pussies", "pussy", "spunk", "xxx", "viagra", "phentermine", "tramadol", "adipex", "advai", "alprazolam", "ambien", "ambian", "amoxicillin", "antivert", "blackjack", "backgammon", "texas", "holdem", "poker", "carisoprodol", "ciara", "ciprofloxacin", "debt", "dating", "porn", "link=", "voyeur", "content-type", "bcc:", "cc:", "document.cookie", "onclick", "onload", "javascript");

  foreach ($badwords as $word)
    if (
      strpos(strtolower($_POST['comments']), $word) !== false || 
      strpos(strtolower($_POST['name']), $word) !== false
    )
      $points += 2;
  
  if (strpos($_POST['comments'], "http://") !== false || strpos($_POST['comments'], "www.") !== false)
    $points += 2;
  if (isset($_POST['nojs']))
    $points += 1;
  if (preg_match("/(<.*>)/i", $_POST['comments']))
    $points += 2;
  if (strlen($_POST['name']) < 3)
    $points += 1;
  if (strlen($_POST['comments']) < 15 || strlen($_POST['comments'] > 1500))
    $points += 2;
  if (preg_match("/[bcdfghjklmnpqrstvwxyz]{7,}/i", $_POST['comments']))
    $points += 1;
  // end score assignments

  foreach($requiredFields as $field) {
    trim($_POST[$field]);
    
    if (!isset($_POST[$field]) || empty($_POST[$field]) && array_pop($error_msg) != "Please fill in all the required fields and submit again.\r\n")
      $error_msg[] = "Please fill in all the required fields and submit again.";
  }

  if (!empty($_POST['name']) && !preg_match("/^[a-zA-Z-'\s]*$/", stripslashes($_POST['name'])))
    $error_msg[] = "The name field must not contain special characters.\r\n";
  if (!empty($_POST['email']) && !preg_match('/^([a-z0-9])(([-a-z0-9._])*([a-z0-9]))*\@([a-z0-9])(([a-z0-9-])*([a-z0-9]))+' . '(\.([a-z0-9])([-a-z0-9_-])?([a-z0-9])+)+$/i', strtolower($_POST['email'])))
    $error_msg[] = "That is not a valid e-mail address.\r\n";
  if (!empty($_POST['url']) && !preg_match('/^(http|https):\/\/(([A-Z0-9][A-Z0-9_-]*)(\.[A-Z0-9][A-Z0-9_-]*)+)(:(\d+))?\/?/i', $_POST['url']))
    $error_msg[] = "Invalid website url.\r\n";
  
  if ($error_msg == NULL && $points <= $maxPoints) {
    $subject = "Consulta curso Programación desde sitio web";
    
    $message = "You received this e-mail message through your website: Programacion \n\n";
    foreach ($_POST as $key => $val) {
      if (is_array($val)) {
        foreach ($val as $subval) {
          $message .= ucwords($key) . ": " . clean($subval) . "\r\n";
        }
      } else {
        $message .= ucwords($key) . ": " . clean($val) . "\r\n";
      }
    }
    $message .= "\r\n";
    $message .= 'IP: '.$_SERVER['REMOTE_ADDR']."\r\n";
    $message .= 'Browser: '.$_SERVER['HTTP_USER_AGENT']."\r\n";
    $message .= 'Points: '.$points;

    if (strstr($_SERVER['SERVER_SOFTWARE'], "Win")) {
      $headers   = "From: $yourEmail\r\n";
    } else {
      $headers   = "From: $yourWebsite <$yourEmail>\r\n"; 
    }
    $headers  .= "Reply-To: {$_POST['email']}\r\n";

    if (mail($yourEmail,$subject,$message,$headers)) {
      if (!empty($thanksPage)) {
        header("Location: $thanksPage");
        exit;
      } else {
        $result = 'Mail enviado correctamente.';
        $disable = true;
      }
    } else {
      $error_msg[] = 'Your mail could not be sent this time. ['.$points.']';
    }
  } else {
    if (empty($error_msg))
      $error_msg[] = 'Your mail looks too much like spam, and could not be sent this time. ['.$points.']';
  }
}
function get_data($var) {
  if (isset($_POST[$var]))
    echo htmlspecialchars($_POST[$var]);
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <!-- FAVICON -->
  <link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon">
  <link rel="icon" href="images/favicon.ico" type="image/x-icon">
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css" integrity="sha384-WskhaSGFgHYWDcbwN70/dfYBj47jz9qbsMId/iRN3ewGhXQFZCSftd1LZCfmhktB"
    crossorigin="anonymous">
  <!-- GOOGLE FONTS -->
  <link href='https://fonts.googleapis.com/css?family=Titillium+Web:400,300,200,700' rel='stylesheet' type='text/css'>
  <link href='https://fonts.googleapis.com/css?family=Roboto+Slab:300,400,700' rel='stylesheet' type='text/css'>
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.0.13/css/all.css" integrity="sha384-DNOHZ68U8hZfKXOrtjWvjxusGo9WQnrNx2sqG0tfsghAvtVlRW3tvkXWZh58N9jp"
    crossorigin="anonymous">
  <link rel="stylesheet" href="css/custom.css">
  <title>Egg Tech - Programación</title>
</head>

<body data-spy="scroll" data-target="#first" data-offset="115">
  <div id="whatsapp" class="whatsapp">
    <a target="_blank" href="https://api.whatsapp.com/send?phone=5492616852036&text=Hola!%20Me%20gustaría%20recibir%20más%20información%20sobre%20el%20curso%20de%20Programación">
      <img src="images/whatsapp.png" width="80" alt="">
    </a>
  </div>
  <header>
    <nav class="fixed-top" data-toggle="affix" role="navigation">
      <div id="first" class="navbar navbar-dark bg-dark navbar-expand-md d-none d-sm-none d-md-block">
        <div class="nav navbar-nav collapse navbar-collapse justify-content-center" id="mainNav">
          <a class="nav-item nav-link active" href="index.php">Inicio</a>
          <a class="nav-item nav-link" href="#dirigido">¿A quién va dirigido?</a>
          <a class="nav-item nav-link" href="#learnbydoing">¿Qué vas a aprender?</a>
          <!-- <a class="nav-item nav-link" href="#profesores">Profesores</a> -->
          <!-- <a class="nav-item nav-link" href="#edudelfuturo">Educación del futuro</a> -->
          <a class="nav-item nav-link" href="#datos">Horarios y Precios</a>
          <a target="_blank" class="nav-item nav-link" href="https://www.facebook.com/EggTechDigital" style="margin-right: -15px">
            <i style="font-size: 1rem" class="fab fa-facebook-square"></i>
          </a>
          <a target="_blank" class="nav-item nav-link" href="https://www.instagram.com/egg_tech/">
            <i style="font-size: 1rem" class="fab fa-instagram"></i>
          </a>
        </div>
      </div>
      <div id="second" class="navbar navbar-light bg-light navbar-expand-sm">
        <div class="container">
          <a class="navbar-brand mx-auto mx-md-0" href="index.php">
            <img class="" src="images/logo.svg" height="67" alt="Isologo Mac a Punto">
          </a>
          <div class="ml-auto mr-3 d-none d-lg-block">
            <span>
              <strong>
                <i class="fab fa-whatsapp mr-2"></i>261-6852-036</strong>
            </span>
          </div>
          <a target="_blank" data-toggle="modal" data-target="#formcontactomodal" data-backdrop="static" data-keyboard="false" class="btn btn-primary btn-egg contraste d-none d-md-block "><strong>INSCRIBITE AHORA</strong></a>
          </a>
        </div>
      </div>
    </nav>
  </header>
  <main>
    <section id="hero" autoplay preload="auto" class="h-100 pb-5 pb-md-0" data-vide-bg="video/ocean" class="videvg" data-vide-options="resizing: true, bgColor: black, muted: true, autoplay: true, position: 0% 0%">
      <div class="container h-100">
        <div class="row h-100 align-items-center">
          <div class="col-10 offset-1 col-md-8 offset-md-2 align-self-center text-center">
            <h1>Convertite en Programador</h1>
            <h1>Sé un experto en desarrollo Web Full Stack</h1>
            <p class="my-4 lead">Aprendé a crear aplicaciones. Diseño front-end y arquitectura back-end. Programación orientada a objetos. Java.
              MySQL. HTML + CSS. Javascript.</p>
            <a href="download/Egg_Programa_Programacion_2018.pdf" target="_blank" class="btn btn-primary btn-egg btn-lg dark mr-lg-4 mb-3 my-md-3"
              style="width: 250px">Programa Completo</a>
            <a href="#" class="btn btn-primary btn-egg btn-lg dark" data-toggle="modal" data-target="#formcontactomodal" style="width: 250px">Inscribite Ahora</a>
          </div>
        </div>
      </div>
    </section>
    <!-- BOTON FACEBOOK MESSENGER <a href="https://m.me/344275365916631">Mensaje</a> -->
    <section id="cuposlimitados" class="limit-panel">
      <div class="container py-5">
        <div class="row py-5 align-items-center">
          <div class="col-12 col-md-3 txtbco text-center my-4 my-md-0">
            <img class="mb-3" src="images/datosIco2.svg" height="60px" alt="Reloj">
            <p>
              <strong>
                <span class="txtylow">INICIA </span>
                <br>
              </strong> AGOSTO 2018</p>
          </div>
          <div class="col-12 col-md-4 mx-auto txtbco text-center my-4 my-md-0">
            <img class="mb-3" src="images/datosIco1.svg" height="60px" alt="">
            <p class="txtylow">
              <strong>CARGA HORARIA</strong>
            </p>
            <p>160hs de clases y mentorías + 400hs disponibles para trabajar en nuestro espacio de Co-Working y Co-Learning.</p>
          </div>
          <div class="col-12 col-md-3 txtbco text-center my-4 my-md-0">
            <img class="mb-3" src="images/datosIco3.svg" height="60px" alt="Pizarrón">
            <p>
              <strong>
                <span class="txtylow">MODALIDAD </span>
                <br>
              </strong> PRESENCIAL</p>
          </div>
        </div>
      </div>
    </section>
    <section id="dirigido" class="padding-section limit-panel">
      <div class="container-fluid">
        <div class="row text-center">
          <div class="col-12 col-lg-6 padding-section p-5 p-md-5">
            <div class="p-md-5">
              <h1 class="mb-4">¡Aprendé creando!</h1>
              <p>
                <strong>Desarrollá proyectos reales</strong>, en equipos multidisciplinarios, creando soluciones que superen los
                desafíos del mercado.
                <br>
                <strong>¡Sé protagonista de la Industria Digital!</strong>
              </p>
            </div>
          </div>
          <div class="col-12 col-lg-6 padding-section p-5 pt-0 p-md-5">
            <div class="p-md-5">
              <h1 class="mb-4">¿A quién va dirigido?</h1>
              <p>A todas aquellas personas que deseen adquirir las herramientas fundamentales para
                <strong>desarrollarse como programadores.</strong>
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>
    <section id="learnbydoing" class="padding-section limit-panel">
      <div class="container py-5">
        <div class="row py-5">
          <div class="col-12 col-md-8 offset-md-2 text-center py-5">
            <h1 class="txtbco">¿Qué vas a aprender?</h1>
            <p class="mt-4">
              <strong>Aprenderás a desarrollar webs desde cero y a crear aplicaciones</strong>, afrontando proyectos de cualquier
              complejidad. Podrás potenciar tu trabajo, comenzar tu propio emprendimiento o unirte compañías líderes del
              mundo digital.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- <section id="profesores" class="padding-section limit-panel"> -->

    <section id="programa" class="padding-section">
      <div class="container pb-5">
        <div class="row pb-5">
          <div class="col-12 col-md-8 offset-md-2 text-center pt-5">
            <img class="mb-4" src="images/programIcon.svg" width="100" alt="Ícono Programa">
            <h1>Programa</h1>
          </div>
        </div>
        <div class="row">
          <div class="col-12 col-md-10 offset-md-1">
            <div class="accordion text-center text-lg-left" id="accordionExample">
              <div class="card">
                <div class="card-header" id="headingOne">
                  <h5 class="mb-0">
                    <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#1" aria-expanded="true" aria-controls="1">
                      1 - Introducción a técnicas de programación
                    </button>
                  </h5>
                </div>
                <div id="1" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionExample">
                  <div class="card-body">
                    <ul>1.1- Conceptos básicos de Informática.</ul>
                    <ul>1.2- Algoritmos. Definición. Tipos de Algoritmos.</ul>
                    <ul>1.3- Lenguajes de Programación. Tipos de Lenguajes. Compiladores.</ul>
                  </div>
                </div>
              </div>
              <div class="card">
                <div class="card-header" id="headingTwo">
                  <h5 class="mb-0">
                    <button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#2" aria-expanded="false" aria-controls="2">
                      2 - Estructura de un programa. Sintaxis. Variables
                    </button>
                  </h5>
                </div>
                <div id="2" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionExample">
                  <div class="card-body">
                    <ul>2.1- Qué es Java? Sintaxis. Palabras reservadas del lenguaje.</ul>
                    <ul>2.2- Instrucciones. Tipos de instrucciones. Tipos de datos. Variables. Constantes. </ul>
                    <ul>2.3- Expresiones y operaciones aritméticas, alfanuméricas, lógicas, relacionales.</ul>
                    <ul>2.4- Declaración de variables, asignaciones, lectura y escritura.</ul>
                  </div>
                </div>
              </div>
              <div class="card">
                <div class="card-header" id="headingThree">
                  <h5 class="mb-0">
                    <button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#3" aria-expanded="false" aria-controls="3">
                      3 - Estructuras de control
                    </button>
                  </h5>
                </div>
                <div id="3" class="collapse" aria-labelledby="headingThree" data-parent="#accordionExample">
                  <div class="card-body">
                    <ul>3.1- Flujos de control. Condicionales simples dobles y múltiples.</ul>
                    <ul>3.2- Condiciones de salida.</ul>
                    <ul>3.3- Estructuras repetitivas. Bucles. Anidaciones.</ul>
                  </div>
                </div>
              </div>
              <div class="card">
                <div class="card-header" id="headingThree">
                  <h5 class="mb-0">
                    <button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#4" aria-expanded="false" aria-controls="4">
                      4 - Variables dimensionadas y estructuradas
                    </button>
                  </h5>
                </div>
                <div id="4" class="collapse" aria-labelledby="headingThree" data-parent="#accordionExample">
                  <div class="card-body">
                    <ul>4.1- Conceptos básicos. Dimensiones. Subíndices. Variables unidimensionales y multidimensionales.</ul>
                    <ul>4.2- Listas. Operaciones.</ul>
                    <ul>4.3- Estructuras. Estructuras anidadas. Operaciones con estructuras.</ul>
                  </div>
                </div>
              </div>
              <div class="card">
                <div class="card-header" id="headingThree">
                  <h5 class="mb-0">
                    <button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#5" aria-expanded="false" aria-controls="5">
                      5 - Programación Orientada a Objetos
                    </button>
                  </h5>
                </div>
                <div id="5" class="collapse" aria-labelledby="headingThree" data-parent="#accordionExample">
                  <div class="card-body">
                    <ul>5.1- Objetos y Clases. Conceptos básicos</ul>
                    <ul>5.2- Análisis y Diseño Orientado a Objetos</ul>
                    <ul>5.3- Los Identificadores, Palabras Clave y Tipos. Arreglos.</ul>
                    <ul>5.4- Interacción entre Objetos.</ul>
                    <ul>5.5- Relaciones. Uno a Uno. Uno a Muchos / Muchos a Uno. Muchos a Muchos.</ul>
                  </div>
                </div>
              </div>
              <div class="card">
                <div class="card-header" id="headingThree">
                  <h5 class="mb-0">
                    <button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#6" aria-expanded="false" aria-controls="6">
                      6 - Métodos, funciones y procedimientos
                    </button>
                  </h5>
                </div>
                <div id="6" class="collapse" aria-labelledby="headingThree" data-parent="#accordionExample">
                  <div class="card-body">
                    <ul>6.1- Métodos. ¿Para que se usan? Variables. Entorno de las variables. Parámetros.</ul>
                    <ul>6.2- Private, public, protected. Scope.</ul>
                    <ul>6.3- Constructores en Java</ul>
                    <ul>6.4- Buenas prácticas de programación</ul>
                  </div>
                </div>
              </div>
              <div class="card">
                <div class="card-header" id="headingThree">
                  <h5 class="mb-0">
                    <button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#7" aria-expanded="false" aria-controls="7">
                      7 - Bases de Datos
                    </button>
                  </h5>
                </div>
                <div id="7" class="collapse" aria-labelledby="headingThree" data-parent="#accordionExample">
                  <div class="card-body">
                    <ul>7.1- Concepto de base de datos. ¿Para qué sirve? </ul>
                    <ul>7.2- SQL. MySQL. ¿Cómo se organiza una base de datos? Instalación. </ul>
                    <ul>7.3- Conexiones a la base de datos.</ul>
                    <ul>7.4- Consultas. SELECT. Clave Primaria. Clave Foránea. JOINS. INSERT, DELETE Y UPDATE. INSERT. DELETE.
                      UPDATE. Tipos de datos. Números. Fechas. Texto. CREATE, ALTER Y DROP.</ul>
                  </div>
                </div>
              </div>
              <div class="card">
                <div class="card-header" id="headingThree">
                  <h5 class="mb-0">
                    <button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#8" aria-expanded="false" aria-controls="8">
                      8 - Metodologías ágiles y control de versiones
                    </button>
                  </h5>
                </div>
                <div id="8" class="collapse" aria-labelledby="headingThree" data-parent="#accordionExample">
                  <div class="card-body">
                    <ul>8.1- Metodologías ágiles:XP, kanban, scrum</ul>
                    <ul>8.2- Introducción a los sistemas de control de versiones, su importancia y sus características a lo largo
                      de la historia. Descripción general de Git y Github (y alternativas como Bitbucket y GitLab)</ul>
                  </div>
                </div>
              </div>
              <div class="card">
                <div class="card-header" id="headingThree">
                  <h5 class="mb-0">
                    <button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#9" aria-expanded="false" aria-controls="9">
                      9 - Introducción desarrollo web
                    </button>
                  </h5>
                </div>
                <div id="9" class="collapse" aria-labelledby="headingThree" data-parent="#accordionExample">
                  <div class="card-body">
                    <ul>9.1- Arquitectura de Internet (cliente / servidor).</ul>
                    <ul>9.2- Stacks y principios de desarrollo web.</ul>
                    <ul>9.3- Frontend / Backend</ul>
                    <ul>9.4- ¿Qué es un programador Full Stack?</ul>
                  </div>
                </div>
              </div>
              <div class="card">
                <div class="card-header" id="headingThree">
                  <h5 class="mb-0">
                    <button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#10" aria-expanded="false" aria-controls="10">
                      10 - HTML + CSS
                    </button>
                  </h5>
                </div>
                <div id="10" class="collapse" aria-labelledby="headingThree" data-parent="#accordionExample">
                  <div class="card-body">
                    <ul>10.1- Estructura y estándares de uso.</ul>
                    <ul>10.2- HTML. Semántica en HTML. Etiquetas Principales. Listas. Formularios Tablas.</ul>
                    <ul>10.3- ¿Qué es CSS y cómo incluirlo en nuestro proyecto? Sintaxis CSS y principales reglas.</ul>
                    <ul>10.4- Selectores. Medidas. Colores. Fuentes. Tools.</ul>
                    <ul>10.5- Preprocesadores. Introducción a SASS.</ul>
                    <ul>10.6- Bootstrap</ul>
                  </div>
                </div>
              </div>
              <div class="card">
                <div class="card-header" id="headingThree">
                  <h5 class="mb-0">
                    <button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#11" aria-expanded="false" aria-controls="11">
                      11 - JavaScript
                    </button>
                  </h5>
                </div>
                <div id="11" class="collapse" aria-labelledby="headingThree" data-parent="#accordionExample">
                  <div class="card-body">
                    <ul>11.1- Acceso al DOM, selectores, agregado/borrado, edición.</ul>
                    <ul>11.2- Objetos, variables, scopes, etc.</ul>
                    <ul>11.3- Manejo extendido: storage, forms, embeds, etc. Pedidos por AJAX.</ul>
                    <ul>11.4- Conceptos básicos de JavaScript. Introducción al manejo de selectores con jQuery. </ul>
                    <ul>11.5- Eventos con jQuery (click, hover, focus, change, Keypress) Animaciones con jQuery (fade, 8.1- easing,
                      parallax, Slide)</ul>
                  </div>
                </div>
              </div>
              <div class="card">
                <div class="card-header" id="headingThree">
                  <h5 class="mb-0">
                    <button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#12" aria-expanded="false" aria-controls="12">
                      12 - JavaScript Avanzado
                    </button>
                  </h5>
                </div>
                <div id="12" class="collapse" aria-labelledby="headingThree" data-parent="#accordionExample">
                  <div class="card-body">
                    <ul>12.2- Sintaxis. Palabras reservadas Variables. Tipos de variables. Operadores.</ul>
                    <ul>12.3- Let y const. Arrow func ons. String Templates.</ul>
                    <ul>12.4- Programación funcional. Array Methods. Clases. Promises.</ul>
                  </div>
                </div>
              </div>
              <div class="card">
                <div class="card-header" id="headingThree">
                  <h5 class="mb-0">
                    <button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#15" aria-expanded="false" aria-controls="15">
                      13 - Cierre de ciclo: Presentación de proyectos realizados.
                    </button>
                  </h5>
                </div>
                <div id="15" class="collapse" aria-labelledby="headingThree" data-parent="#accordionExample">
                  <div class="card-body">
                    <p>Mostrá todo el trabajo realizado.</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- <section id="edudelfuturo" class="limit-panel"></section> -->

    <section id="cuposlimitados" class="limit-panel">
      <div class="container py-5">
        <div class="row py-5">
          <div class="col-12 col-md-8 offset-md-2 text-center py-5">
            <h1 class="txtylow mb-4">¡Cupos limitados!</h1>
            <a a target="_blank" data-toggle="modal" data-target="#formcontactomodal" data-backdrop="static" data-keyboard="false" class="btn btn-primary btn-egg btn-lg" style="color:#333; font-weight: 700; ">INSCRIBITE AHORA</a>
          </div>
        </div>
      </div>
    </section>
    <section id="reviews" class="padding-section limit-panel">
      <div class="container pb-5">
        <div class="row pb-5">
          <div class="col-12 col-md-8 offset-md-2 text-center py-5">
            <img class="mb-4" src="images/tickIcon.svg" width="100" alt="Ícono Programa">
            <h1>Proceso de Inscripción</h1>
          </div>
        </div>
        <div class="row">
          <div class="col-12 col-lg-10 offset-lg-1">
            <div class="row align-items-start justify-content-around text-center">

              <div class="col-12 col-md-4 col-lg my-3 my-lg-0">
                <img src="images/step1.svg" width="86" alt="Paso 1" data-toggle="tooltip" data-placement="bottom" data-html="true" title="<p class='mt-2'>¡Recordá que los cupos son limitados!</p>">
                <h2 class="mt-4">Completá tus datos</h2>
                <div class="d-block  d-sm-none d-md-none">¡Recordá que los cupos son limitados!</div>
              </div>

              <div class="col-1 d-none d-lg-block" style="height: 5px; background: #ffc500; margin-top:40px"></div>

              <div class="col-12 col-md-4 col-lg my-3 my-lg-0">
                <img src="images/step2.svg" width="86" alt="Paso 2" data-toggle="tooltip" data-placement="bottom" data-html="true" title="<p class='mt-2'>Una vez inscripto, asistí a un encuentro con uno de tus directores para planificar tus objetivos y metas de forma personalizada.</p>">
                <h2 class="mt-4">Planificá tus objetivos</h2>
                <div class="d-block  d-sm-none d-md-none">Una vez inscripto, asistí a un encuentro con uno de tus directores para planificar tus objetivos y metas
                  de forma personalizada.</div>
              </div>

              <div class="col-1 d-none d-lg-block" style="height: 5px; background: #ffc500; margin-top:40px"></div>

              <div class="col-12 col-md-4 col-lg my-3 my-lg-0">
                <img src="images/step3.svg" width="86" alt="Paso 3" data-toggle="tooltip" data-placement="bottom" data-html="true" title="<p class='mt-2'>¡Aprendé creando!</p>">
                <h2 class="mt-4">Comenzá</h2>
                <div class="d-block  d-sm-none d-md-none">¡Aprendé creando!</div>
              </div>

            </div>
          </div>
        </div>
      </div>
    </section>
    <section id="datos" class="padding-section limit-panel pb-5">
      <div class="container pb-5">
        <div class="row py-5">
          <div class="col-12 col-md-8 offset-md-2 text-center">
            <img class="mb-4" src="images/infoIcon.svg" width="100" alt="Ícono Programa">
            <h1 class="txtbco">Horarios y Precios</h1>
          </div>
        </div>
        <div class="row">
          <div id="horariosyprecios" class="col-md-10 offset-md-1">
            <div class="p-5 text-center" style="background: #3e4646; border-radius:15px 15px 0 0">
              <h5 class="txtbco">Lunes, miércoles y viernes (viernes cada 15 días)</h5>
              <span class="txtbco mt-5">+ 400hs disponibles para trabajar en nuestro espacio de aprendizaje y co-working.</span>
            </div>
            <div class="d-flex flex-column flex-lg-row justify-content-around p-5" style="background: white;">
              <div class="text-center my-4 my-lg-0">
                <span class="d-block">
                  <h5 class="txtylow">
                    <strong>INICIO</strong>
                  </h5>
                </span>
                <span class="d-block">
                  <h5>13/08/2018</h5>
                </span>
              </div>
              <div class="text-center my-4 my-lg-0">
                <span class="d-block">
                  <h5 class="txtylow">
                    <strong>FIN</strong>
                  </h5>
                </span>
                <span class="d-block">
                  <h5>07/12/2018</h5>
                </span>
              </div>
              <div class="text-center my-4 my-lg-0">
                <span class="d-block">
                  <h5 class="txtylow">
                    <strong>HORARIO</strong>
                  </h5>
                </span>
                <span class="d-block">
                  <h5>18:30 A 21:30</h5>
                </span>
              </div>
              <div class="text-center align-self-center my-4 my-lg-0">
                <span class="d-block w-100">
                  <h5 class="txtylow">
                    <strong>PRECIO</strong>
                  </h5>
                </span>
                <span class="d-inline-block" style="vertical-align: middle;">
                  <h6 class="mr-2" style="margin: 0px">
                    <strong>12 cuotas de: </strong>
                  </h6>
                </span>
                <span class="d-inline-block" style="vertical-align: middle;">
                  <h3 class="" style="margin-bottom: 0px">
                    <strong>$2.800</strong>
                  </h3>
                </span>
              </div>
            </div>
            <div class="py-5 text-center" style="background: #3e4646; border-radius:0 0 15px 15px">
              <h4 class="txtylow">¡
                <strong>25% de descuento</strong> en un solo pago!</h4>
              <span class="txtbco">Aceptamos todas las tarjetas de débito, crédito y efectivo</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- <section id="faq" class="padding-section"></section> -->

    <section class="mb-5">
      <div class="col-12 col-md-8 offset-md-2 text-center pt-5">
        <h1>Nuestras Alianzas</h1>
        <br>
      </div>

      <div class="container" class="mb-5">

        <div class="row text-center">
          <div class="col-6 col-md p-4">
            <img class="img-fluid" src="images/logos/embarca.jpg" alt="Logo MyDesign">
          </div>
          <div class="col-6 col-md p-4">
            <img class="img-fluid" src="images/logos/mdz.jpg" alt="Logo MyDesign">
          </div>
          <div class="col-6 col-md p-4">
            <img class="img-fluid" src="images/logos/olegario.jpg" alt="Logo MyDesign">
          </div>
          <div class="col-6 col-md p-4">
            <img class="img-fluid" src="images/logos/agilmentor.jpg" alt="Logo MyDesign">
          </div>
          <div class="col-6 col-md p-4">
            <img class="img-fluid" src="images/logos/tienda_naranja.jpg" alt="Logo MyDesign">
          </div>
          <div class="col-6 col-md p-4 d-block d-md-none">
            <img class="img-fluid" src="images/logos/mydesign.jpg" alt="Logo MyDesign">
          </div>
        </div>

        <div class="row text-center mt-lg-5">
          <div class="col-6 col-md p-4 d-none d-md-block">
            <img class="img-fluid" src="images/logos/mydesign.jpg" alt="Logo MyDesign">
          </div>
          <div class="col-6 col-md p-4">
            <img class="img-fluid" src="images/logos/la_proa.jpg" alt="Logo MyDesign">
          </div>
          <div class="col-6 col-md p-4">
            <img class="img-fluid" src="images/logos/polenta.jpg" alt="Logo MyDesign">
          </div>
          <div class="col-6 col-md p-4">
            <img class="img-fluid" src="images/logos/dictioz.jpg" alt="Logo MyDesign">
          </div>
          <div class="col-6 col-md p-4">
            <img class="img-fluid" src="images/logos/los_andes.jpg" alt="Logo MyDesign">
          </div>
        </div>

      </div>

    </section>

    <section id="social" class="limit-panel">
      <div class="container py-5">
        <div class="row py-5">
          <div class="col-12 col-md-8 offset-md-2 text-center py-5">
            <a target="_blank" href="https://www.facebook.com/EggTechDigital">
              <img class="mr-3" src="images/facebook.svg" width="100px" alt="">
            </a>
            <a target="_blank" href="https://www.instagram.com/egg_tech/">
              <img class="ml-3" src="images/instagram.svg" width="100px" alt="">
            </a>
          </div>
        </div>
      </div>
    </section>

  </main>
  <!-- Optional JavaScript -->
  <!-- jQuery first, then Popper.js, then Bootstrap JS -->
  <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo"
    crossorigin="anonymous"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49"
    crossorigin="anonymous"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.min.js" integrity="sha384-smHYKdLADwkXOn1EmN1qk/HfnUcbVRZyYmZ4qpPea6sjB/pTJ0euyQp0Mk8ck+5T"
    crossorigin="anonymous"></script>
  <script src="node_modules/Vide-0.5.1/dist/jquery.vide.min.js"></script>
  <script>
    $(document).ready(function () {
      //FORCE VIDEO PLAY - SAFARI 11 ISSUE
      $(window).on("load", function () {
        $('#hero').data('vide').getVideoObject().play();
      })
    });
  </script>
  <script>
    $(window).scroll(function () {
      if ($(document).scrollTop() > 50) {
        $('#first').addClass('shrink');
      } else {
        $('#first').removeClass('shrink');
      }
    });
    $(window).scroll(function () {
      if ($(document).scrollTop() > 50) {
        $('#third').addClass('shrink');
      } else {
        $('#third').removeClass('shrink');
      }
    });
    $(window).scroll(function () {
      if ($(document).scrollTop() > 100) {
        $('#whatsapp').addClass('washrink');
      } else {
        $('#whatsapp').removeClass('washrink');
      }
    });
  </script>
  <script>
    $(function () {
      $('[data-toggle="tooltip"]').tooltip()
    })
  </script>
  <script>
    window.addEventListener("hashchange", function () {
      window.scrollTo(window.scrollX, window.scrollY - 60);
    });
  </script>
  <!-- MODALES -->
  <!-- Modal -->
  <!-- Modal -->
  <div class="modal fade bd-example-modal-lg" id="formcontactomodal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true" style="padding-right: 0px !important">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" data-dismiss="modal" class="close" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="container-fluid">
          <div class="row text-center">
            <div class="col-10 offset-1">
              <h2 class="mb-4">¿Estás interesado en Programación?</h2>
              <p class="mb-4">¡Dejanos tus datos y te contactamos para iniciar el proceso de inscripción!</p>
            </div>
          </div>
          <form role="form" id="contact-form" enctype="multipart/form-data" method="POST" action="<?php echo basename(__FILE__);
?>">
            <?php
            if (!empty($error_msg)) {
	echo '<p style="color:#e20613" class="error">ERROR: '. implode("<br />", $error_msg) . "</p>";
}
if ($result != NULL) {
	?>
              <script>
                $('#formcontactomodal').modal('show')
              </script>
            <?php 
              echo '<p class="text-center success bg-success p-2" style="color:white; border-radius:5px; font-size:1rem;">'. $result . "</p>";
}
?>
            <div id="messages" class="messages text-center"></div>
            <div class="controls">
              <div class="row">
                <div class="col-md-12">
                  <div class="form-group">
                    <!-- <label for="form_name">Firstname *</label>  -->
                    <input id="form_name" type="text" name="name" class="form-control input-lg" placeholder="Nombre y Apellido *" required="required" data-error="Campo requerido.">
                    <p class="help-block bg-warning"></p>
                  </div>
                </div>
                <div class="col-md-12">
                  <div class="form-group">
                    <!-- <label for="form_lastname">Lastname *</label>  -->
                    <input id="form_email" type="email" name="email" class="form-control input-lg" placeholder="Email *" required="required" data-error="Email válido es requerido.">
                    <p class="help-block bg-warning"></p>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-12">
                  <div class="form-group">
                    <!-- <label for="form_email">Email *</label>  -->
                    <input id="form_phone" type="number" name="phone" class="form-control input-lg" required="required" placeholder="Whatsapp *" data-error="Campo requerido.">
                    <p class="help-block bg-warning"></p>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-12">
                  <div class="form-group">
                    <!-- <label for="form_message">Message *</label>  -->
                    <textarea id="form_message" name="comments" class="form-control input-lg" placeholder="Mensaje *" rows="4" required="required" data-error="Por favor, escribe un mensaje."></textarea>
                    <p class="help-block bg-warning"></p>
                  </div>
                </div>
                <div class="col-md-12 text-center">
                  <input type="submit" class="btn btn-primary btn-egg contraste" value="ENVIAR">
                </div>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>



</body>

</html>