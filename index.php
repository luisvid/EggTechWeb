<?php
// OPTIONS - PLEASE CONFIGURE THESE BEFORE USE!

$yourEmail = "hola@eggtech.com.ar"; // the email address you wish to receive these mails through
$yourWebsite = "EGG TECH"; // the name of your website
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
    $subject = "Automatic Form Email";
    
    $message = "You received this e-mail message through your website: \n\n";
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
  <title>Egg Tech</title>
</head>

<body data-spy="scroll" data-target="#first" data-offset="115">
  <div id="whatsapp" class="whatsapp">
    <a target="_blank" href="https://api.whatsapp.com/send?phone=5492616852036&text=Hola!%20Me%20gustaría%20recibir%20más%20información">
      <img src="images/whatsapp.png" width="80" alt="">
    </a>
  </div>
  <header>
    <nav class="fixed-top" data-toggle="affix" role="navigation">

      <!-- <div id="first" class="navbar navbar-dark bg-dark navbar-expand-md d-none d-sm-none d-md-block">
        <div class="nav navbar-nav collapse navbar-collapse justify-content-center" id="mainNav">
          <a class="nav-item nav-link active" href="index.php">Inicio</a>
          <a class="nav-item nav-link" href="#dirigido">¿A quién va dirigido?</a>
          <a class="nav-item nav-link" href="#learnbydoing">¿Qué vas a aprender?</a>
          <a class="nav-item nav-link" href="#profesores">Profesores</a>
          <a class="nav-item nav-link" href="#edudelfuturo">Educación del futuro</a>
          <a class="nav-item nav-link" href="#datos">Horarios y Precios</a>
          <a target="_blank" class="nav-item nav-link" href="https://www.facebook.com/EggTechDigital" style="margin-right: -15px">
            <i style="font-size: 1rem" class="fab fa-facebook-square"></i>
          </a>
          <a target="_blank" class="nav-item nav-link" href="https://www.instagram.com/egg_tech/">
            <i style="font-size: 1rem" class="fab fa-instagram"></i>
          </a>
        </div>
      </div> -->

      <div id="second" class="navbar navbar-light bg-light navbar-expand-sm">
        <div class="container">
          <a class="navbar-brand mx-auto mx-md-0" href="#">
            <img class="" src="images/logo.svg" height="67" alt="Egg Tech">
          </a>
          <div class="ml-auto mr-3 d-none d-lg-block">
            <span>
              <strong>
                <i class="fab fa-whatsapp mr-2"></i>261-6852-036</strong>
            </span>
          </div>
          <!-- <a target="_blank" href="https://m.me/344275365916631" class="btn btn-primary btn-egg contraste d-none d-md-block "><strong>¡INSCRIBITE AHORA!</strong></a> -->
        </div>
      </div>

    </nav>
  </header>
  <main>
    <section id="hero" autoplay preload="auto" class="h-100 pb-5 pb-md-0" data-vide-bg="video/ocean" class="videvg" data-vide-options="resizing: true, bgColor: black, muted: true, autoplay: true, position: 0% 0%">
      <div class="container h-100">
        <div class="row h-100 align-items-center">
          <div class="col-12 col-md-8 offset-md-2 align-self-center text-center">
            <h1>¡Aprendé Marketing Digital y Programación de la mano de los mejores!</h1>
            <p class="my-4 lead"></p>
            <a href="marketing.php" class="btn btn-primary btn-egg btn-lg dark mr-md-4 mb-3 mb-md-0" style="width: 250px">Marketing Digital</a>
            <a href="programacion.php" class="btn btn-primary btn-egg btn-lg dark" style="width: 250px">Programación</a>
          </div>
        </div>
      </div>
    </section>


    <section id="edudelfuturo" class="limit-panel">
      <div class="container py-5">
        <div class="row pt-5">
          <div class="col-12 col-md-8 offset-md-2 text-center pb-5">
            <h1 class="txtylow">¡Educación del futuro!</h1>
          </div>
        </div>
        <div class="row text-center">
          <div class="col-12 col-lg-6">
            <div class="p-3 pb-5">
              <h1 class="mb-4 txtbco">La mejor forma de aprender</h1>
              <p class="txtbco">Serás
                <strong>protagonista de un sistema de aprendizaje innovador</strong> que se basa en la
                <strong>cooperación</strong> para alcanzar tus metas.
                <strong>Te acompañaremos durante todo tu aprendizaje</strong>, y serás parte de una
                <strong>comunidad que sigue viva más allá del curso.</strong>
              </p>
            </div>
          </div>
          <div class="col-12 col-lg-6">
            <div class="p-3 pb-5">
              <h1 class="mb-4 txtbco">En un lugar único</h1>
              <p class="txtbco">Las clases se dictan en el
                <strong>ecosistema emprendedor tecnológico de Mendoza</strong>, en un campus donde la innovación y la creatividad se respiran en
                cada rincón. Trabajarás junto a profesionales del sector
                <strong>compartiendo con tus profesores</strong> fuera del horario de clase.</p>
            </div>
          </div>
        </div>
        <div class="row text-center">
          <div class="col-10 offset-1 col-md-10 offset-md-1 align-self-center py-5" style="background-color: #ffca00; border-radius:10px; font-family: 'Roboto Slab', serif;">
            <strong>
              <h2 class="txtbco" style="font-weight: 700; font-size: 3rem !important">¡Aprendé creando!</h2>
            </strong>
          </div>
        </div>
        <div class="row text-center">
          <div class="col-12 col-lg-6">
            <div class="p-5">
              <h1 class="mb-4 txtbco">Certificá tu experiencia</h1>
              <p class="txtbco">Además del curso, certificaremos tu experiencia en el desarrollo de
                <strong>proyectos reales</strong>.
              </p>
            </div>
          </div>
          <div class="col-12 col-lg-6">
            <div class="p-5">
              <h1 class="mb-4 txtbco">Acceso a trabajos</h1>
              <p class="txtbco">Postulate y
                <strong>recibí ofertas</strong> a través de nuestra bolsa de trabajo.
                <strong>Estarás</strong> inmerso en un mundo de posibilidades laborales.
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section id="faq" class="padding-section">
      <div class="container pb-5">
        <div class="row pb-5">
          <div class="col-12 col-md-8 offset-md-2 text-center pt-5">
            <h1>Preguntas Frecuentes</h1>
          </div>
        </div>
        <div class="row">
          <div class="col-12 col-md-10 offset-md-1">
            <div class="accordion text-center text-lg-left" id="accordionExample2">
              <div class="card">
                <div class="card-header" id="headingOne">
                  <h5 class="mb-0">
                    <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#16" aria-expanded="true" aria-controls="16">
                      ¿Los cursos son presenciales?
                    </button>
                  </h5>
                </div>
                <div id="16" class="collapse" aria-labelledby="headingOne" data-parent="#accordionExample2">
                  <div class="card-body">
                    <p>Sí, todos nuestros cursos son presenciales.</p>
                  </div>
                </div>
              </div>
              <div class="card">
                <div class="card-header" id="headingTwo">
                  <h5 class="mb-0">
                    <button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#17" aria-expanded="false" aria-controls="17">
                      No tengo experiencia en el campo, ¿puedo hacer el curso igual?
                    </button>
                  </h5>
                </div>
                <div id="17" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionExample2">
                  <div class="card-body">
                    <p>¡Claro que sí! nuestra idea es formar profesionales que vengan de todos los ámbitos para potenciar
                      la creatividad de los equipos que formamos. Además, como es un sistema educativo diferente, tendrás
                      tutorías constantes.</p>
                  </div>
                </div>
              </div>
              <div class="card">
                <div class="card-header" id="headingThree">
                  <h5 class="mb-0">
                    <button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#18" aria-expanded="false" aria-controls="18">
                      ¿Qué necesito para ser admitido?
                    </button>
                  </h5>
                </div>
                <div id="18" class="collapse" aria-labelledby="headingThree" data-parent="#accordionExample2">
                  <div class="card-body">
                    <p>Tener pasión por el mundo digital. Con eso es suficiente. Te haremos una entrevista personal para conocer
                      tus intereses, tus objetivos y tu perfil para ayudarte a planificar tus metas con el curso.</p>
                  </div>
                </div>
              </div>
              <div class="card">
                <div class="card-header" id="headingThree">
                  <h5 class="mb-0">
                    <button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#19" aria-expanded="false" aria-controls="19">
                      Si ya tengo conocimientos básicos de Marketing o Programación, ¿vale la pena hacer el curso?
                    </button>
                  </h5>
                </div>
                <div id="19" class="collapse" aria-labelledby="headingThree" data-parent="#accordionExample2">
                  <div class="card-body">
                    <p>Sí. Desde Egg Tech proponemos que el conocimiento trascienda el programa o lo que se ve durante el cursado.
                      La idea es formar vínculos con emprendedores, colegas y profesores, que conozcas cómo se maneja el
                      mundo de las Startups mendocinas y puedas generar contactos que te permitan llevar tus ideas a la
                      acción.
                    </p>
                  </div>
                </div>
              </div>
              <div class="card">
                <div class="card-header" id="headingThree">
                  <h5 class="mb-0">
                    <button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#20" aria-expanded="false" aria-controls="20">
                      ¿Tendré acceso al campus de trabajo?
                    </button>
                  </h5>
                </div>
                <div id="20" class="collapse" aria-labelledby="headingThree" data-parent="#accordionExample2">
                  <div class="card-body">
                    <p>¡Sí! Con la matrícula del curso tendrás acceso a nuestro campus de trabajo, donde podrás
                      encontrarte con tus compañeros y colegas fuera del horario de clase. </p>
                  </div>
                </div>
              </div>
              <div class="card">
                <div class="card-header" id="headingThree">
                  <h5 class="mb-0">
                    <button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#21" aria-expanded="false" aria-controls="21">
                      ¿Necesito llevar mi computadora?
                    </button>
                  </h5>
                </div>
                <div id="21" class="collapse" aria-labelledby="headingThree" data-parent="#accordionExample2">
                  <div class="card-body">
                    <p>Sí, para cursar es necesario que traigas tu propia compu.</p>
                  </div>
                </div>
              </div>
              <div class="card">
                <div class="card-header" id="headingThree">
                  <h5 class="mb-0">
                    <button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#22" aria-expanded="false" aria-controls="22">
                      ¿En qué consiste el trabajo final?
                    </button>
                  </h5>
                </div>
                <div id="22" class="collapse" aria-labelledby="headingThree" data-parent="#accordionExample2">
                  <div class="card-body">
                    <p>Trabajarás con emprendedores y Startups de Mendoza, en equipo junto a tus compañeros, desarrollando proyectos
                      reales, que verán la luz cuando termines el curso.</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

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
              <h2 class="mb-4">¿Estás interesado en Marketing Digital?</h2>
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