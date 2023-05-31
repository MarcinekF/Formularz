<?php

// Konfiguracja mailera
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__);
$dotenv->load();

//Przypisanie zmiennych z pliku env zawierający konfigurację SMTP
$DB_HOST = getenv('DB_Host');
$DB_USERNAME=getenv('DB_Username');
$DB_PASSWORD=getenv('DB_Password');
$DB_SMTPSecure=getenv('DB_SMTPSecure');
$DB_Port=getenv('DB_Port');

// Pobranie danych z formularza
$transportFrom = $_POST['transport_from'];
$transportTo = $_POST['transport_to'];
$airplaneType = $_POST['airplane_type'];
$transportDate = $_POST['transport_date'];
$cargoName = $_POST['cargo_name'];
$cargoWeight = $_POST['cargo_weight'];
$cargoType = $_POST['cargo_type'];

// Sprawdzenie poprawności daty transportu
$dayOfWeek = date('N', strtotime($transportDate));
if ($dayOfWeek >= 6) {
    die('Transport może się odbywać tylko od poniedziałku do piątku.');
}

for ($i = 0; $i < count($cargoName); $i++) {
    $cargoWeightItem = $cargoWeight[$i];
    $cargoTypeItem = $cargoType[$i];

    // Sprawdzenie maksymalnej wagi ładunku dla wybranego samolotu
    if ($airplaneType == 'Airbus A380' && $cargoWeightItem > 35000) {
        die('Waga pojedynczego ładunku przekracza maksymalny dopuszczalny limit dla samolotu Airbus A380.');
    } elseif ($airplaneType == 'Boeing 747' && $cargoWeightItem > 38000) {
        die('Waga pojedynczego ładunku przekracza maksymalny dopuszczalny limit dla samolotu Boeing 747.');
    }
}


$emailRecipient = ''; //W zaleznosci od rodzaju samolotu, formularz przesylany jest na konkretny adres email
if ($airplaneType == 'Airbus A380') {
    $emailRecipient = 'airbus@lemonmind.com';
} elseif ($airplaneType == 'Boeing 747') {
    $emailRecipient = 'boeing@lemonmind.com';
}

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = $DB_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = $DB_USERNAME;
    $mail->Password = $DB_PASSWORD;
    $mail->SMTPSecure = $DB_SMTPSecure;
    $mail->Port = $DB_Port;

    /*  --------------------------------------------------------  Kod sluzacy do testowania 
        $mail->setFrom('your-email@example.com', 'Your Name');
    $mail->addAddress('recipient@example.com', 'Recipient Name');
    */
    $mail->setFrom('your-email@example.com', 'Your Name');
    $mail->addAddress($emailRecipient, 'Recipient Name');

    $mail->isHTML(true);
    $mail->Subject = 'Formularz przewozu towarow - Dane';
    $mail->Body = generateEmailBody();

    // Przesyłanie załączników
    if (isset($_FILES['transport_documents'])) {
        for ($i = 0; $i < count($_FILES['transport_documents']['name']); $i++) {
            $tmpFilePath = $_FILES['transport_documents']['tmp_name'][$i];
            if ($tmpFilePath != "") {
                $mail->addAttachment($tmpFilePath, $_FILES['transport_documents']['name'][$i]);
            }
        }
    }

    $mail->send();
    echo 'E-mail został wyslany.';
} catch (Exception $e) {
    echo "Blad podczas wysyłania wiadomości e-mail: {$mail->ErrorInfo}";
}

function generateEmailBody()
{
    global $transportFrom, $transportTo, $airplaneType, $transportDate, $cargoName, $cargoWeight, $cargoType;

    $body = '<h2>Dane formularza przewozu towarow:</h2>';
    $body .= '<p>Transport z: ' . $transportFrom . '</p>';
    $body .= '<p>Transport do: ' . $transportTo . '</p>';
    $body .= '<p>Typ samolotu: ' . $airplaneType . '</p>';
    $body .= '<p>Data transportu: ' . $transportDate . '</p>';

    $body .= '<h3>Lista zgloszonych towarow:</h3>';
    $body .= '<table>';
    $body .= '<tr><th>Nazwa ladunku</th><th>Ciezar ladunku (kg)</th><th>Typ ladunku</th></tr>';
    for ($i = 0; $i < count($cargoName); $i++) {
        $body .= '<tr>';
        $body .= '<td>' . $cargoName[$i] . '</td>';
        $body .= '<td>' . $_POST['cargo_weight'][$i] . '</td>';
        $body .= '<td>' . $cargoType[$i] . '</td>';
        $body .= '</tr>';
    }
    $body .= '</table>';

    return $body;
}