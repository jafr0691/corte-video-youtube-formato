<?php
require __DIR__ . '/vendor/autoload.php';

use YoutubeDl\YoutubeDl;
use YoutubeDl\Exception\CopyrightException;
use YoutubeDl\Exception\NotFoundException;
use YoutubeDl\Exception\PrivateVideoException;

$dl = new YoutubeDl([
    'continue' => true, // force resume of partially downloaded files. By default, youtube-dl will resume downloads if possible.
    'format' => 'mp4',
]);
// For more options go to https://github.com/rg3/youtube-dl#user-content-options

$_POST["url"] = "https://www.youtube.com/watch?v=qBlYZByaZiA&list=PLpFLMeMeQv8ObiUDy2AmvkUUOc9gQpcLS&index=26";
$_POST["fomrato"] = "mp3";
$_POST["startTime"] = 2.5;
$_POST["endTime"] = 8.9;

$dl->setDownloadPath("/videos");
// Enable debugging
/*$dl->debug(function ($type, $buffer) {
    if (\Symfony\Component\Process\Process::ERR === $type) {
        echo 'ERR > ' . $buffer;
    } else {
        echo 'OUT > ' . $buffer;
    }
});*/

function elimina_acentos($text)
{
    $text = htmlentities($text, ENT_QUOTES, 'UTF-8');
       // $text = strtolower($text);
    $patron = array (
            // Espacios, puntos y comas por guion
            //'/[\., ]+/' => ' ',

            // Vocales
        '/\+/' => '',
        '/&agrave;/' => 'a',
        '/&egrave;/' => 'e',
        '/&igrave;/' => 'i',
        '/&ograve;/' => 'o',
        '/&ugrave;/' => 'u',

        '/&aacute;/' => 'a',
        '/&eacute;/' => 'e',
        '/&iacute;/' => 'i',
        '/&oacute;/' => 'o',
        '/&uacute;/' => 'u',

        '/&acirc;/' => 'a',
        '/&ecirc;/' => 'e',
        '/&icirc;/' => 'i',
        '/&ocirc;/' => 'o',
        '/&ucirc;/' => 'u',

        '/&atilde;/' => 'a',
        '/&etilde;/' => 'e',
        '/&itilde;/' => 'i',
        '/&otilde;/' => 'o',
        '/&utilde;/' => 'u',

        '/&auml;/' => 'a',
        '/&euml;/' => 'e',
        '/&iuml;/' => 'i',
        '/&ouml;/' => 'o',
        '/&uuml;/' => 'u',


            // Otras letras y caracteres especiales
        '/&aring;/' => 'a',
        '/&ntilde;/' => 'n',

            // Agregar aqui mas caracteres si es necesario

    );

    $text = preg_replace(array_keys($patron),array_values($patron),$text);
    return $text;
}

function descargaFormato($formato,$cort_from,$cort_till,$name){
    $nom = glob('./videos/*');
    list($p,$b,$nomcarp) = explode('/', $nom[0]);
    $cambnameespacio = str_replace(' ', '-', $name);
    $cambname = elimina_acentos($cambnameespacio);
    if(!rename("./videos/".$nomcarp, "./videosurl/".$cambname.".mp4")){
        rename("./videos/".$nomcarp, "./videosurl/Youtube.mp4");
        $cambname = "Youtube";
    }
    if($formato == "mp3"){
        shell_exec('ffmpeg -i '.escapeshellcmd("./videosurl/".$cambname.".mp4").' -f mp3 -ab 192000 -vn '.escapeshellcmd("./audios/".$cambname.".mp3"));
        shell_exec('ffmpeg -i ./audios/'.$cambname.'.mp3 -ss '.$cort_from.' -t '.$cort_till.' ./audiosCorte/'.$cambname.'.mp3');
        $ruta = "./audiosCorte";
    }else{
        shell_exec('ffmpeg -i ./videosurl/'.$cambname.'.mp4 -ss '.$cort_from.' -t '.$cort_till.' ./videosCorte/'.$cambname.'.mp4');
        $ruta = "./videosCorte";
    }

    $res = array("url" => $ruta."/".$cambname.".".$formato);
    function eliminarCarpeta($dir) {
        if(!$dh = @opendir($dir)) return;
        while (false !== ($current = readdir($dh))) {
            if($current != '.' && $current != '..') {
                if (!@unlink($dir.'/'.$current))
                    eliminarCarpeta($dir.'/'.$current);
            }
        }
        closedir($dh);
        @rmdir($dir);
    }

    if (file_exists('./videos/')) {
        eliminarCarpeta("./videos/");
    }

    if (!file_exists('./videos/')) {
        mkdir('./videos/', 0777, true);
        chmod('./videos/', 0777);
    }
    exit(json_encode($res));
}



try {
    $video = $dl->download($_POST["url"]);
    if (glob('./videos/*')) {

        descargaFormato($_POST["formato"],$_POST["startTime"],$_POST["endTime"],$video->getTitle());

    }
     // Will return Phonebloks
    // $video->getFile(); // \SplFileInfo instance of downloaded file
} catch (NotFoundException $e) {
    // Video not found
    exit($e);
} catch (PrivateVideoException $e) {
    // Video is private
    exit($e);
} catch (CopyrightException $e) {
    // The YouTube account associated with this video has been terminated due to multiple third-party notifications of copyright infringement
    exit($e);
} catch (\Exception $e) {
    // Failed to download
    exit($e);
}