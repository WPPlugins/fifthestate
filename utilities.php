<?php
namespace FifthEstate;

function curl_get( $url, $header ) {
    $ch = curl_init( $url );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
    if ( ! ( empty( $header ) ) ) {
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $header );
    }
    $data = curl_exec( $ch );
    curl_close( $ch );
    return $data;
}

function curl_post( $url, $data, $header ) {
    $ch = curl_init( $url );
    curl_setopt( $ch, CURLOPT_POST, 1 );
    curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
    curl_setopt( $ch, CURLOPT_HTTPHEADER, $header );
    $response = curl_exec( $ch );
    $error_message = curl_error( $ch );
    curl_close( $ch );
    return $response ? $response : $error_message;
}
