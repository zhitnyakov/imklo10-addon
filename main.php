<?php
if ( !defined( 'BASEPATH' ) ) exit( 'No direct script access allowed' );

class Main extends CI_Controller
{

    public function index()
    {
    }

    public function postback( $id, $status, $pay )
    {
        $this->load->model( 'main_model' );
        $this->main_model->update( 'leads', [ 'id' => $id ], [ 'approve' => $status, 'pay' => $pay ] );
    }

    public function impostback()
    {
        $this->load->model( 'main_model' );
        $this->load->model( 'back_model' );
        $id = $this->input->get( 'id' );
        if ( $id ) {
            $upd[ 'pp_id' ]  = $this->input->get( 'pid' );
            $upd[ 'pay' ]    = $this->input->get( 'pay' );
            $upd[ 'status' ] = $this->input->get( 'status' );
            $upd[ 'number' ] = $this->input->get( 'comment' );
            $upd[ 'ip' ]     = $this->input->get( 'ip' );

            $approve_status = $this->input->get( 'approve_status' );
            $wait_status    = $this->input->get( 'wait_status' );
            $reject_status  = $this->input->get( 'reject_status' );

            if ( !$approve_status ) $approve_status = 'confirmed';
            if ( !$wait_status ) $wait_status = 'lead,hold';
            if ( !$reject_status ) $reject_status = 'cancelled,rejected,trash';

            $approve_status = explode( ",", $approve_status );
            if ( in_array( $upd[ 'status' ], $approve_status ) ) {
                $upd[ 'status' ]  = 'confirmed';
                $upd[ 'approve' ] = 'confirmed';
            }

            $wait_status = explode( ",", $wait_status );
            if ( in_array( $upd[ 'status' ], $wait_status ) ) {
                $upd[ 'status' ]  = 'hold';
                $upd[ 'approve' ] = 'hold';
            }

            $reject_status = explode( ",", $reject_status );
            if ( in_array( $upd[ 'status' ], $reject_status ) ) {
                $upd[ 'status' ]  = 'decline';
                $upd[ 'approve' ] = 'decline';
            }

            $up = $this->main_model->update( 'leads', [ 'id' => $id ], $upd );

            if ( !$up AND $upd[ 'pp_id' ] ) {
                $bd_lead = $this->main_model->get( 'leads', [ 'pp_id' => $upd[ 'pp_id' ] ] );
                if ( $bd_lead ) {
                    $this->main_model->update( 'leads', [ 'pp_id' => $upd[ 'pp_id' ] ], $upd );
                } else {
                    if ( $upd[ 'ip' ] ) {
                        $api_data         = $this->back_model->check( $upd[ 'ip' ] );
                        $upd[ 'country' ] = $api_data[ 'iso2' ];
                    }
                    $upd[ 'name' ]  = '-';
                    $upd[ 'phone' ] = '-';
                    $upd[ 'host' ]  = $this->input->get( 'host' );
                    $upd[ 'host' ]  = str_replace( "www.", "", $upd[ 'host' ] );
                    $upd[ 'date' ]  = date( "Y-m-d H:i:s" );
                    $this->main_model->insert( 'leads', $upd );
                }
            }
        }
    }

    public function nadd_domain()
    {
        $this->load->model( 'main_model' );

        $data[ 'succes' ] = false;
        $error_data       = [];

        $api_token = $this->input->post( 'api_token' );
        $key       = $this->main_model->get( 'users' );
        if ( !$key[ 'api_token' ] ) $error_data[ 'errors' ][] = 'token';
        if ( !$api_token ) $error_data[ 'errors' ][] = 'token';
        if ( $key[ 'api_token' ] != $api_token ) $error_data[ 'errors' ][] = 'token';

        $traf_type = [ 'ANY' => 0, 'MOB' => 2, 'DESC' => 3 ];

        $new[ 'domain' ]     = trim( $this->input->post( 'domain' ) );
        $new[ 'link' ]       = urldecode( $this->input->post( 'black' ) );
        $new[ 'white_link' ] = urldecode( $this->input->post( 'white' ) );
        $geo_data            = $this->input->post( 'geo' );
        $traf                = $this->input->post( 'type' );
        @$new[ 'traf' ] = $traf_type[ $traf ];

        $vowels          = [ "https://", "www.", "http://", "/", ":" ];
        $new[ 'domain' ] = str_replace( $vowels, "", $new[ 'domain' ] );

        if ( !$new[ 'domain' ] ) $error_data[ 'errors' ][] = 'no domain';
        if ( !$geo_data ) $error_data[ 'errors' ][] = 'no geo';
        if ( !$traf ) $error_data[ 'errors' ][] = 'no type';

        if ( $error_data ) {
            $data[ 'error' ] = $error_data;
        } else {
            $geos = explode( ",", $geo_data );
            foreach ( $geos as $geo ) {
                $new[ 'country' ] = $geo;
                $check_domain     = $this->main_model->get( 'domains', [ 'domain' => $new[ 'domain' ], 'country' => $new[ 'country' ] ] );
                if ( $check_domain ) {
                    $data[ 'succes' ]         = false;
                    $error_data[ 'errors' ][] = 'double domain ' . $new[ 'domain' ] . '-' . $new[ 'country' ];
                } else {
                    $data[ 'succes' ] = true;
                    $this->main_model->insert( 'domains', $new );
                }
            }
        }

        echo json_encode( $data );
    }

    public function nlist_domains()
    {
        $this->load->model( 'main_model' );

        $api_token = $this->input->post( 'api_token' );
        $key       = $this->main_model->get( 'users' );
        if ( !$key[ 'api_token' ] ) $error_data[ 'errors' ][] = 'token';
        if ( !$api_token ) $error_data[ 'errors' ][] = 'token';
        if ( $key[ 'api_token' ] != $api_token ) $error_data[ 'errors' ][] = 'token';

        if ( $error_data ) {
            $data[ 'error' ] = $error_data;
        } else {
            $data[ 'succes' ]  = true;
            $data[ 'domains' ] = $this->main_model->getAll( 'domains' );
        }
        echo json_encode( $data );
    }

    public function nlist_country()
    {
        $this->load->model( 'main_model' );

        $api_token = $this->input->post( 'api_token' );
        $key       = $this->main_model->get( 'users' );
        if ( !$key[ 'api_token' ] ) $error_data[ 'errors' ][] = 'token';
        if ( !$api_token ) $error_data[ 'errors' ][] = 'token';
        if ( $key[ 'api_token' ] != $api_token ) $error_data[ 'errors' ][] = 'token';

        if ( $error_data ) {
            $data[ 'error' ] = $error_data;
        } else {
            $data[ 'succes' ]  = true;
            $data[ 'country' ] = $this->main_model->getAll( 'country' );
        }
        echo json_encode( $data );
    }

    public function ndel_domain()
    {
        $this->load->model( 'main_model' );

        $api_token = $this->input->post( 'api_token' );
        $key       = $this->main_model->get( 'users' );
        if ( !$key[ 'api_token' ] ) $error_data[ 'errors' ][] = 'token';
        if ( !$api_token ) $error_data[ 'errors' ][] = 'token';
        if ( $key[ 'api_token' ] != $api_token ) $error_data[ 'errors' ][] = 'token';

        $error_data       = [];
        $data[ 'succes' ] = false;

        $domain = $this->input->post( 'domain' );
        if ( !$domain ) $error_data[ 'errors' ][] = 'no post domain';;

        if ( $error_data ) {
            $data[ 'error' ] = $error_data;
        } else {
            $data[ 'succes' ] = true;
            $this->main_model->delete( 'domains', [ 'domain' => $domain ] );
        }

        echo json_encode( $data );
    }

    public function delete_logs()
    {
        $this->load->model( 'main_model' );
        $this->main_model->truncate( 'logs' );
        $this->main_model->truncate( 'black_ip' );
    }

    public function add_lead()
    {
        $this->load->model( 'main_model' );
        $this->load->model( 'back_model' );
        $add[ 'old_phone' ] = $this->input->get( 'old_phone' );
        $add[ 'phone' ]     = $this->input->get( 'phone' );
        $add[ 'name' ]      = $this->input->get( 'name' );
        $add[ 'ip' ]        = $this->input->get( 'ip' );
        $add[ 'number' ]    = $this->input->get( 'number' );
        $add[ 'host' ]      = $this->input->get( 'host' );
        $add[ 'host' ]      = str_replace( "www.", "", $add[ 'host' ] );
        $add[ 'date' ]      = date( "Y-m-d H:i:s" );
        $add[ 'status' ]    = 0;

        $api_data         = $this->back_model->check( $add[ 'ip' ] );
        $add[ 'country' ] = $api_data[ 'iso2' ];

        $lead = $this->main_model->insert( 'leads', $add );
        echo json_encode( [ 'id' => $lead ] );
    }

    public function update_lead()
    {
        $this->load->model( 'main_model' );

        $id           = $this->input->get( 'id' );
        $response_api = $this->input->get( 'response_api' );
        $this->main_model->update( 'leads', [ 'id' => $id ], [ 'response_api' => $response_api ] );
    }

    public function jscheck_ip( $include = false )
    {
        // error_reporting(E_ALL);
        // ini_set('display_errors', 1);

        // die('js');

        $this->load->model( 'main_model' );
        $this->load->model( 'back_model' );

        $error_data = [];

        $log[ 'ip' ]     = @$_SERVER[ "HTTP_CF_CONNECTING_IP" ] ? @$_SERVER[ "HTTP_CF_CONNECTING_IP" ] : $_SERVER[ "REMOTE_ADDR" ];
        $log[ 'domain' ] = @$_SERVER[ "HTTP_REFERER" ];
        $log[ 'domain' ] = parse_url( $log[ 'domain' ], PHP_URL_HOST );
        $log[ 'domain' ] = str_replace( "www.", "", $log[ 'domain' ] );

        $log[ 'referer' ]    = @$_SERVER[ "HTTP_REFERER" ];
        $log[ 'user_agent' ] = $_SERVER[ 'HTTP_USER_AGENT' ];

        if ( !$log[ 'referer' ] ) $log[ 'referer' ] = 'no';

        $log[ 'land' ]    = $this->input->post( 'land' );
        $log[ 'date' ]    = date( "Y-m-d H:i:s" );
        $log[ 'headers' ] = json_encode( apache_request_headers() );
        $log[ 'utm' ]     = $this->input->post( 'utm' );

        $api_data = $this->back_model->check( $log[ 'ip' ] );

//        echo '<pre>';
//        var_dump( $log );
//        die();

        if ( !$api_data ) $error_data[ 'errors' ][] = 'Лицензия не активна';

        $log[ 'net' ]   = $api_data[ 'cidr' ];
        $log[ 'descr' ] = $api_data[ 'isp' ];
        $db_country     = $api_data[ 'iso2' ];

        $_SERVER[ 'HTTP_USER_AGENT' ] = $log[ 'user_agent' ];
        $this->load->library( 'user_agent' );
        if ( $this->agent->is_mobile() ) {
            $log[ 'mobile' ] = 1;
        } else {
            $log[ 'mobile' ] = 0;
        }

//нормализация юзер агента
        $fb_repl = stristr( $log[ 'user_agent' ], ' [FB_IAB', true );
        if ( $fb_repl ) $log[ 'user_agent' ] = $fb_repl;
        $fb_repl1 = stristr( $log[ 'user_agent' ], ' [FBAN/', true );
        if ( $fb_repl1 ) $log[ 'user_agent' ] = $fb_repl1;
        $in_repl = stristr( $log[ 'user_agent' ], ' Instagram', true );
        if ( $in_repl ) $log[ 'user_agent' ] = $in_repl;

//https://www.facebook.com/business/help/1514372351922333 определение предпросмотра
        $headers = json_decode( $log[ 'headers' ], true );
        if ( @$headers[ 'X-Purpose' ] == 'preview' && ( @$headers[ 'X-FB-HTTP-Engine' ] == 'Liger' || @$headers[ 'x-fb-http-engine' ] == 'Liger' ) ) {
            $error_data[ 'errors' ][] = 'prewiew.';
            $log[ 'preview' ]         = 1;
        } else {
            $log[ 'preview' ] = 0;
        }

        if ( $log[ 'preview' ] == 0 ) {
            $log_id = $this->main_model->insert( 'logs', $log );
        }

//проверяем необходимые переменные
        if ( !$log[ 'ip' ] ) $error_data[ 'errors' ][] = 'Не передан IP адрес';
        if ( !$log[ 'domain' ] ) $error_data[ 'errors' ][] = 'Домен не добавлен в админ панель';

        if ( $api_data[ 'bot' ] ) {
            $error_data[ 'errors' ][] = 'Бот (api)';
        }

        if ( $log[ 'preview' ] == 0 ) {
            $this->main_model->update( 'logs', [ 'id' => $log_id ], [ 'country' => $db_country ] );
        }

        $count_klick = $this->main_model->count( 'logs', [ 'domain' => $log[ 'domain' ], 'preview' => 0 ], 'id' );
        if ( $count_klick <= 15 ) {
            //$error_data['errors'][] = 'min 15 leads.';
        }

//может быть несколько записей одного домена с разными гео и данными

//берем из базы данные о домене, выдавать их в случае если нужно показывать IP!
        $check_domain = $this->main_model->get( 'domains', [ 'domain' => $log[ 'domain' ] ] );
        if ( !$check_domain ) {
            $check_domain[ 'white_link' ] = '';
            $check_domain[ 'country' ]    = 'no_detected';
            $check_domain[ 'metrika_id' ] = '';
            $error_data[ 'errors' ][]     = 'Домен не добавлен в админ панель';
        }

//проверяем блэк лист сетей
        $db_net = $this->main_model->getAll( 'black_net' );
        foreach ( $db_net as $net ) {
            $mas       = explode( "/", $net[ 'net' ] );
            $check_net = $this->main_model->net_search( $log[ 'ip' ], $mas[ 0 ], $mas[ 1 ] );
            if ( $check_net ) {
                $error_net[]              = $net[ 'net' ];
                $error_data[ 'errors' ][] = 'Подсеть заблокирована ' . $net[ 'net' ];
            }
        }

//Если нет в бане по подсети
        if ( empty( $error_net ) ) {
//проверяем блэк лист IP
            $check_blacl_list = $this->main_model->get( 'black_ip', [ 'ip' => $log[ 'ip' ] ] );
            if ( $check_blacl_list ) {
                $error_data[ 'errors' ][] = 'IP адрес в черном списке';
            } else {
                //проверять имя подсети на допустимое
                if ( empty( $error_net ) ) {
                    if ( $log[ 'descr' ] AND $log[ 'net' ] ) {
                        $black_net_names = $this->main_model->getAll( 'black_isp' );
                        foreach ( $black_net_names as $black_net_name ) {
                            $posn = strpos( $log[ 'descr' ], $black_net_name[ 'name' ] );
                            if ( $posn === false ) {
                            } else {
                                $error_net[] = 'add';
                                $this->main_model->insert( 'black_net', [ 'net' => $log[ 'net' ], 'cause' => 'auto ban' ] );
                                $error_data[ 'errors' ][] = 'Запрещенное ISP, Подсеть автоматически заблокирована';
                            }
                        }
                    }
                }

                //проверка страны
                $check_domain_country = $this->main_model->get( 'domains', [ 'domain' => $log[ 'domain' ], 'country' => $db_country ] );
                if ( $check_domain_country ) {
                    $check_domain = $check_domain_country;
                } else {
                    $error_data[ 'errors' ][] = 'Вход с запрещенного ГЕО, IP добавлен в черный список';
                    $this->main_model->insert( 'black_ip', [ 'ip' => $log[ 'ip' ], 'country' => $db_country, 'cause' => 'Вход с запрещенного ГЕО', 'comment' => $log[ 'descr' ] ] );
                }

                if ( $this->agent->platform == 'Unknown Platform' ) {
                    $error_data[ 'errors' ][] = 'Unknown Platform, IP добавлен в черный список';
                    $this->main_model->insert( 'black_ip', [ 'ip' => $log[ 'ip' ], 'country' => $db_country, 'cause' => 'Unknown Platform', 'comment' => $log[ 'descr' ] ] );
                }

                if ( !$this->agent->browser ) {
                    $error_data[ 'errors' ][] = 'Не определен браузер, IP добавлен в черный список';
                    $this->main_model->insert( 'black_ip', [ 'ip' => $log[ 'ip' ], 'country' => $db_country, 'cause' => 'Не определен браузер', 'comment' => $log[ 'descr' ] ] );
                }

                if ( $this->agent->robot ) {
                    $error_data[ 'errors' ][] = 'Робот OS, IP добавлен в черный список';
                    $this->main_model->insert( 'black_ip', [ 'ip' => $log[ 'ip' ], 'country' => $db_country, 'cause' => 'Робот OS', 'comment' => $log[ 'descr' ] ] );
                }

                if ( @$headers[ 'Cache-Control' ] == "no-cache" ) {
                    $error_data[ 'errors' ][] = 'Кэш фильтр, IP добавлен в черный список';
                    $this->main_model->insert( 'black_ip', [ 'ip' => $log[ 'ip' ], 'country' => $db_country, 'cause' => 'Кэш фильтр', 'comment' => $log[ 'descr' ] ] );
                }

                if ( $this->agent->platform == 'Linux' AND !$this->agent->is_mobile() ) {
                    $error_data[ 'errors' ][] = 'Десктоп линукс фильтр, IP добавлен в черный список';
                    $this->main_model->insert( 'black_ip', [ 'ip' => $log[ 'ip' ], 'country' => $db_country, 'cause' => 'Десктоп линукс фильтр', 'comment' => $log[ 'descr' ] ] );
                }

                // if(!$headers['Accept-Language']){
                // 	$error_data['errors'][] = 'Language';
                // }

//Если юзер агент менялся более 10х раз
                $count_ua = $this->main_model->count( 'logs', [ 'ip' => $log[ 'ip' ], 'preview' => 0 ], 'user_agent' );
                if ( @$count_ua >= 10 ) {
                    $error_data[ 'errors' ][] = 'Частая смена UA, IP добавлен в черный список';
                    $this->main_model->insert( 'black_ip', [ 'ip' => $log[ 'ip' ], 'country' => $db_country, 'cause' => 'Частая смена UA', 'comment' => $log[ 'descr' ] ] );
                }

                //проверять юзер агент на допустимый
                $black_user_agents = $this->main_model->getAll( 'black_ua' );
                foreach ( $black_user_agents as $black_user_agent ) {
                    $pos = strpos( $log[ 'user_agent' ], $black_user_agent[ 'user_agent' ] );
                    if ( $pos === false ) {
                    } else {
                        $error_data[ 'errors' ][] = 'UA заблокирован';
                        $check_blacl_list         = $this->main_model->get( 'black_ip', [ 'ip' => $log[ 'ip' ] ] );
                        if ( !$check_blacl_list ) {
                            $error_data[ 'errors' ][] = 'UA заблокирован, IP добавлен в черный список';
                            $this->main_model->insert( 'black_ip', [ 'ip' => $log[ 'ip' ], 'country' => $db_country, 'cause' => 'UA заблокирован', 'comment' => $log[ 'descr' ] ] );
                        }
                    }
                }

//если не передан юзер агент
                if ( !$log[ 'user_agent' ] ) {
                    $error_data[ 'errors' ][] = 'Не передан UA, IP добавлен в черный список';
                    $this->main_model->insert( 'black_ip', [ 'ip' => $log[ 'ip' ], 'country' => $db_country, 'cause' => 'Не передан UA', 'comment' => $log[ 'descr' ] ] );
                }

//если не передан реферер
                if ( @$log[ 'referer' ] ) {
                    $ref = strpos( $log[ 'referer' ], 'yandex.ru/clck/jsredir' );
                    if ( $ref !== false ) {
                        $error_data[ 'errors' ][] = 'Реферер фильтр, IP добавлен в черный список';
                        $this->main_model->insert( 'black_ip', [ 'ip' => $log[ 'ip' ], 'country' => $db_country, 'cause' => 'Реферер фильтр', 'comment' => $log[ 'descr' ] ] );
                    }
                    // $this->main_model->insert('black_ip', ['ip' => $log['ip'], 'country' => $db_country, 'cause' => 'no_ref', 'comment' => $log['descr']]);
                }

//если не переданы заголовки запроса
                if ( !$log[ 'headers' ] ) {
                    $error_data[ 'errors' ][] = 'Не переданы заголовки запроса';
                    // $this->main_model->insert('black_ip', ['ip' => $log['ip'], 'country' => $db_country, 'cause' => 'no_headers', 'comment' => $log['descr']]);
                }

                if ( @$check_domain[ 'traf' ] == 1 ) {
//любой
                } else if ( @$check_domain[ 'traf' ] == 2 ) {
//моб
                    if ( $log[ 'mobile' ] == 0 ) {
                        $error_data[ 'errors' ][] = 'Десктоп трафик, IP добавлен в черный список';
                        $this->main_model->insert( 'black_ip', [ 'ip' => $log[ 'ip' ], 'country' => $db_country, 'cause' => 'Десктоп трафик', 'comment' => $log[ 'descr' ] ] );
                    }
                } else if ( @$check_domain[ 'traf' ] == 3 ) {
//деск
                    if ( $log[ 'mobile' ] == 1 ) {
                        $error_data[ 'errors' ][] = 'Мобильный трафик, IP добавлен в черный список';
                        $this->main_model->insert( 'black_ip', [ 'ip' => $log[ 'ip' ], 'country' => $db_country, 'cause' => 'Мобильный трафик', 'comment' => $log[ 'descr' ] ] );
                    }
                }
            }
        }

        if ( @$check_domain[ 'shows' ] == 1 ) {
            $db_res                       = 'black';
            $check_domain[ 'white_link' ] = false;
            $check_domain[ 'result' ]     = 1;
            $check_domain[ 'log_id' ]     = $log_id;
            $result                       = json_encode( $check_domain );
            if ( $include ) {
                echo 'var xmlHttp = new XMLHttpRequest();
                        xmlHttp.open( "GET", "' . $check_domain[ 'link' ] . '", false ); // false for synchronous request
                        xmlHttp.send( null );
                        document.open();
                        document.write(xmlHttp.responseText);
                        document.close();';
            } else {
                echo '
document.addEventListener(\'DOMContentLoaded\', function(){
	if(window.screen.width  && window.screen.height && window.screen.width != 2000 && window.screen.height != 2000){

document.body.innerHTML = \'\';

var css = \'html,body,iframe{ height: 100%; margin: 0;}\',
    head = document.head || document.getElementsByTagName(\'head\')[0],
    style = document.createElement(\'style\');

style.type = \'text/css\';
if (style.styleSheet){
  style.styleSheet.cssText = css;
} else {
  style.appendChild(document.createTextNode(css));
}
head.appendChild(style);

var ifrm = document.createElement("iframe");
ifrm.setAttribute("src", "' . $check_domain[ 'link' ] . '?" + location.search);
ifrm.style.width = "100%";
ifrm.style.height = "100%";
document.body.appendChild(ifrm);
}});';
            }
        } else if ( @$check_domain[ 'shows' ] == 2 ) {
            $error_data[ 'errors' ]     = [];
            $error_data[ 'errors' ][]   = 'Включен показ белого сайта';
            $db_res                     = 'white';
            $error_data[ 'result' ]     = 0;
            $error_data[ 'log_id' ]     = $log_id;
            $error_data[ 'white_link' ] = $check_domain[ 'white_link' ];
            $error_data[ 'metrika_id' ] = $check_domain[ 'metrika_id' ];
            $result                     = json_encode( $error_data );
            echo "console.log('ok');";
        } else {
            if ( $error_data ) {
                $db_res                     = 'white';
                $error_data[ 'result' ]     = 0;
                $error_data[ 'log_id' ]     = $log_id;
                $error_data[ 'white_link' ] = $check_domain[ 'white_link' ];
                $error_data[ 'metrika_id' ] = $check_domain[ 'metrika_id' ];
                $result                     = json_encode( $error_data );
                echo "console.log('ok');";
            } else {
                $db_res                       = 'black';
                $check_domain[ 'white_link' ] = false;
                $check_domain[ 'result' ]     = 1;
                $check_domain[ 'log_id' ]     = $log_id;
                $result                       = json_encode( $check_domain );

                if ( $include ) {
                    echo 'var xmlHttp = new XMLHttpRequest();
                        xmlHttp.open( "GET", "' . $check_domain[ 'link' ] . '", false ); // false for synchronous request
                        xmlHttp.send( null );
                        document.open();
                        document.write(xmlHttp.responseText);
                        document.close();';
                } else {
                    echo '
document.addEventListener(\'DOMContentLoaded\', function(){
	if(window.screen.width  && window.screen.height && window.screen.width != 2000 && window.screen.height != 2000){

document.body.innerHTML = \'\';

var css = \'html,body,iframe{ height: 100%; margin: 0;}\',
    head = document.head || document.getElementsByTagName(\'head\')[0],
    style = document.createElement(\'style\');

style.type = \'text/css\';
if (style.styleSheet){
  style.styleSheet.cssText = css;
} else {
  style.appendChild(document.createTextNode(css));
}
head.appendChild(style);

var ifrm = document.createElement("iframe");
ifrm.setAttribute("src", "' . $check_domain[ 'link' ] . '?" + location.search);
ifrm.style.width = "100%";
ifrm.style.height = "100%";
document.body.appendChild(ifrm);
}});';
                }
            }
        }

//пишем в базу рзультат
        if ( $log[ 'preview' ] == 0 ) $this->main_model->update( 'logs', [ 'id' => $log_id ], [ 'response_api' => $result, 'result' => $db_res ] );
    }

    public function check_ip()
    {
        $this->load->model( 'main_model' );
        $this->load->model( 'back_model' );

        $error_data = [];

        $log[ 'ip' ]         = $this->input->post( 'ip' );
        $log[ 'domain' ]     = $this->input->post( 'domain' );
        $log[ 'domain' ]     = str_replace( "www.", "", $log[ 'domain' ] );
        $log[ 'referer' ]    = $this->input->post( 'referer' );
        $log[ 'user_agent' ] = $this->input->post( 'user_agent' );
        $log[ 'land' ]       = $this->input->post( 'land' );
        $log[ 'date' ]       = date( "Y-m-d H:i:s" );
        $log[ 'headers' ]    = $this->input->post( 'headers' );
        $log[ 'utm' ]        = $this->input->post( 'utm' );

        $api_data = $this->back_model->check( $log[ 'ip' ] );

        if ( !$api_data ) $error_data[ 'errors' ][] = 'Лицензия не активна';

        $log[ 'net' ]   = $api_data[ 'cidr' ];
        $log[ 'descr' ] = $api_data[ 'isp' ];
        $db_country     = $api_data[ 'iso2' ];

        $_SERVER[ 'HTTP_USER_AGENT' ] = $log[ 'user_agent' ];
        $this->load->library( 'user_agent' );
        if ( $this->agent->is_mobile() ) {
            $log[ 'mobile' ] = 1;
        } else {
            $log[ 'mobile' ] = 0;
        }

//нормализация юзер агента
        $fb_repl = stristr( $log[ 'user_agent' ], ' [FB_IAB', true );
        if ( $fb_repl ) $log[ 'user_agent' ] = $fb_repl;
        $fb_repl1 = stristr( $log[ 'user_agent' ], ' [FBAN/', true );
        if ( $fb_repl1 ) $log[ 'user_agent' ] = $fb_repl1;
        $in_repl = stristr( $log[ 'user_agent' ], ' Instagram', true );
        if ( $in_repl ) $log[ 'user_agent' ] = $in_repl;

//https://www.facebook.com/business/help/1514372351922333 определение предпросмотра
        $headers = json_decode( $log[ 'headers' ], true );
        if ( @$headers[ 'X-Purpose' ] == 'preview' && ( @$headers[ 'X-FB-HTTP-Engine' ] == 'Liger' || @$headers[ 'x-fb-http-engine' ] == 'Liger' ) ) {
            $error_data[ 'errors' ][] = 'prewiew.';
            $log[ 'preview' ]         = 1;
        } else {
            $log[ 'preview' ] = 0;
        }

        if ( $log[ 'preview' ] == 0 ) {
            $log_id = $this->main_model->insert( 'logs', $log );
        }

//проверяем необходимые переменные
        if ( !$log[ 'ip' ] ) $error_data[ 'errors' ][] = 'Не передан IP адрес';
        if ( !$log[ 'domain' ] ) $error_data[ 'errors' ][] = 'Домен не добавлен в админ панель';

        if ( $api_data[ 'bot' ] ) {
            $error_data[ 'errors' ][] = 'Бот (api)';
        }

        if ( $log[ 'preview' ] == 0 ) {
            $this->main_model->update( 'logs', [ 'id' => $log_id ], [ 'country' => $db_country ] );
        }

        $count_klick = $this->main_model->count( 'logs', [ 'domain' => $log[ 'domain' ], 'preview' => 0 ], 'id' );
        if ( $count_klick <= 15 ) {
            //$error_data['errors'][] = 'min 15 leads.';
        }

//может быть несколько записей одного домена с разными гео и данными

//берем из базы данные о домене, выдавать их в случае если нужно показывать IP!
        $check_domain = $this->main_model->get( 'domains', [ 'domain' => $log[ 'domain' ] ] );
        if ( !$check_domain ) {
            $check_domain[ 'white_link' ] = '';
            $check_domain[ 'country' ]    = 'no_detected';
            $check_domain[ 'metrika_id' ] = '';
            $error_data[ 'errors' ][]     = 'Домен не добавлен в админ панель';
        }

//проверяем блэк лист сетей
        $db_net = $this->main_model->getAll( 'black_net' );
        foreach ( $db_net as $net ) {
            $mas       = explode( "/", $net[ 'net' ] );
            $check_net = $this->main_model->net_search( $log[ 'ip' ], $mas[ 0 ], $mas[ 1 ] );
            if ( $check_net ) {
                $error_net[]              = $net[ 'net' ];
                $error_data[ 'errors' ][] = 'Подсеть заблокирована ' . $net[ 'net' ];
            }
        }

//Если нет в бане по подсети
        if ( empty( $error_net ) ) {
//проверяем блэк лист IP
            $check_blacl_list = $this->main_model->get( 'black_ip', [ 'ip' => $log[ 'ip' ] ] );
            if ( $check_blacl_list ) {
                $error_data[ 'errors' ][] = 'IP адрес в черном списке';
            } else {
                //проверять имя подсети на допустимое
                if ( empty( $error_net ) ) {
                    if ( $log[ 'descr' ] AND $log[ 'net' ] ) {
                        $black_net_names = $this->main_model->getAll( 'black_isp' );
                        foreach ( $black_net_names as $black_net_name ) {
                            $posn = strpos( $log[ 'descr' ], $black_net_name[ 'name' ] );
                            if ( $posn === false ) {
                            } else {
                                $error_net[] = 'add';
                                $this->main_model->insert( 'black_net', [ 'net' => $log[ 'net' ], 'cause' => 'auto ban' ] );
                                $error_data[ 'errors' ][] = 'Запрещенное ISP, Подсеть автоматически заблокирована';
                            }
                        }
                    }
                }

                //проверка страны
                $check_domain_country = $this->main_model->get( 'domains', [ 'domain' => $log[ 'domain' ], 'country' => $db_country ] );
                if ( $check_domain_country ) {
                    $check_domain = $check_domain_country;
                } else {
                    $error_data[ 'errors' ][] = 'Вход с запрещенного ГЕО, IP добавлен в черный список';
                    $this->main_model->insert( 'black_ip', [ 'ip' => $log[ 'ip' ], 'country' => $db_country, 'cause' => 'Вход с запрещенного ГЕО', 'comment' => $log[ 'descr' ] ] );
                }

                if ( $this->agent->platform == 'Unknown Platform' ) {
                    $error_data[ 'errors' ][] = 'Unknown Platform, IP добавлен в черный список';
                    $this->main_model->insert( 'black_ip', [ 'ip' => $log[ 'ip' ], 'country' => $db_country, 'cause' => 'Unknown Platform', 'comment' => $log[ 'descr' ] ] );
                }

                if ( !$this->agent->browser ) {
                    $error_data[ 'errors' ][] = 'Не определен браузер, IP добавлен в черный список';
                    $this->main_model->insert( 'black_ip', [ 'ip' => $log[ 'ip' ], 'country' => $db_country, 'cause' => 'Не определен браузер', 'comment' => $log[ 'descr' ] ] );
                }

                if ( $this->agent->robot ) {
                    $error_data[ 'errors' ][] = 'Робот OS, IP добавлен в черный список';
                    $this->main_model->insert( 'black_ip', [ 'ip' => $log[ 'ip' ], 'country' => $db_country, 'cause' => 'Робот OS', 'comment' => $log[ 'descr' ] ] );
                }

                if ( @$headers[ 'Cache-Control' ] == "no-cache" ) {
                    $error_data[ 'errors' ][] = 'Кэш фильтр, IP добавлен в черный список';
                    $this->main_model->insert( 'black_ip', [ 'ip' => $log[ 'ip' ], 'country' => $db_country, 'cause' => 'Кэш фильтр', 'comment' => $log[ 'descr' ] ] );
                }

                if ( $this->agent->platform == 'Linux' AND !$this->agent->is_mobile() ) {
                    $error_data[ 'errors' ][] = 'Десктоп линукс фильтр, IP добавлен в черный список';
                    $this->main_model->insert( 'black_ip', [ 'ip' => $log[ 'ip' ], 'country' => $db_country, 'cause' => 'Десктоп линукс фильтр', 'comment' => $log[ 'descr' ] ] );
                }

                // if(!$headers['Accept-Language']){
                // 	$error_data['errors'][] = 'Language';
                // }

//Если юзер агент менялся более 10х раз
                $count_ua = $this->main_model->count( 'logs', [ 'ip' => $log[ 'ip' ], 'preview' => 0 ], 'user_agent' );
                if ( @$count_ua >= 10 ) {
                    $error_data[ 'errors' ][] = 'Частая смена UA, IP добавлен в черный список';
                    $this->main_model->insert( 'black_ip', [ 'ip' => $log[ 'ip' ], 'country' => $db_country, 'cause' => 'Частая смена UA', 'comment' => $log[ 'descr' ] ] );
                }

                //проверять юзер агент на допустимый
                $black_user_agents = $this->main_model->getAll( 'black_ua' );
                foreach ( $black_user_agents as $black_user_agent ) {
                    $pos = strpos( $log[ 'user_agent' ], $black_user_agent[ 'user_agent' ] );
                    if ( $pos === false ) {
                    } else {
                        $error_data[ 'errors' ][] = 'UA заблокирован';
                        $check_blacl_list         = $this->main_model->get( 'black_ip', [ 'ip' => $log[ 'ip' ] ] );
                        if ( !$check_blacl_list ) {
                            $error_data[ 'errors' ][] = 'UA заблокирован, IP добавлен в черный список';
                            $this->main_model->insert( 'black_ip', [ 'ip' => $log[ 'ip' ], 'country' => $db_country, 'cause' => 'UA заблокирован', 'comment' => $log[ 'descr' ] ] );
                        }
                    }
                }

//если не передан юзер агент
                if ( !$log[ 'user_agent' ] ) {
                    $error_data[ 'errors' ][] = 'Не передан UA, IP добавлен в черный список';
                    $this->main_model->insert( 'black_ip', [ 'ip' => $log[ 'ip' ], 'country' => $db_country, 'cause' => 'Не передан UA', 'comment' => $log[ 'descr' ] ] );
                }

//если не передан реферер
                if ( @$log[ 'referer' ] ) {
                    $ref = strpos( $log[ 'referer' ], 'yandex.ru/clck/jsredir' );
                    if ( $ref !== false ) {
                        $error_data[ 'errors' ][] = 'Реферер фильтр, IP добавлен в черный список';
                        $this->main_model->insert( 'black_ip', [ 'ip' => $log[ 'ip' ], 'country' => $db_country, 'cause' => 'Реферер фильтр', 'comment' => $log[ 'descr' ] ] );
                    }
                    // $this->main_model->insert('black_ip', ['ip' => $log['ip'], 'country' => $db_country, 'cause' => 'no_ref', 'comment' => $log['descr']]);
                }

//если не переданы заголовки запроса
                if ( !$log[ 'headers' ] ) {
                    $error_data[ 'errors' ][] = 'Не переданы заголовки запроса';
                    // $this->main_model->insert('black_ip', ['ip' => $log['ip'], 'country' => $db_country, 'cause' => 'no_headers', 'comment' => $log['descr']]);
                }

                if ( @$check_domain[ 'traf' ] == 1 ) {
//любой
                } else if ( @$check_domain[ 'traf' ] == 2 ) {
//моб
                    if ( $log[ 'mobile' ] == 0 ) {
                        $error_data[ 'errors' ][] = 'Десктоп трафик, IP добавлен в черный список';
                        $this->main_model->insert( 'black_ip', [ 'ip' => $log[ 'ip' ], 'country' => $db_country, 'cause' => 'Десктоп трафик', 'comment' => $log[ 'descr' ] ] );
                    }
                } else if ( @$check_domain[ 'traf' ] == 3 ) {
//деск
                    if ( $log[ 'mobile' ] == 1 ) {
                        $error_data[ 'errors' ][] = 'Мобильный трафик, IP добавлен в черный список';
                        $this->main_model->insert( 'black_ip', [ 'ip' => $log[ 'ip' ], 'country' => $db_country, 'cause' => 'Мобильный трафик', 'comment' => $log[ 'descr' ] ] );
                    }
                }
            }
        }

        if ( @$check_domain[ 'shows' ] == 1 ) {
            $db_res                       = 'black';
            $check_domain[ 'white_link' ] = false;
            $check_domain[ 'result' ]     = 1;
            $check_domain[ 'log_id' ]     = $log_id;
            $result                       = json_encode( $check_domain );
        } else if ( @$check_domain[ 'shows' ] == 2 ) {
            $error_data[ 'errors' ]     = [];
            $error_data[ 'errors' ][]   = 'Включен показ белого сайта';
            $db_res                     = 'white';
            $error_data[ 'result' ]     = 0;
            $error_data[ 'log_id' ]     = $log_id;
            $error_data[ 'white_link' ] = $check_domain[ 'white_link' ];
            $error_data[ 'metrika_id' ] = $check_domain[ 'metrika_id' ];
            $result                     = json_encode( $error_data );
        } else {
            if ( $error_data ) {
                $db_res                     = 'white';
                $error_data[ 'result' ]     = 0;
                $error_data[ 'log_id' ]     = $log_id;
                $error_data[ 'white_link' ] = $check_domain[ 'white_link' ];
                $error_data[ 'metrika_id' ] = $check_domain[ 'metrika_id' ];
                $result                     = json_encode( $error_data );
            } else {
                $db_res                       = 'black';
                $check_domain[ 'white_link' ] = false;
                $check_domain[ 'result' ]     = 1;
                $check_domain[ 'log_id' ]     = $log_id;
                $result                       = json_encode( $check_domain );
            }
        }

        if ( $log[ 'preview' ] == 0 ) $this->main_model->update( 'logs', [ 'id' => $log_id ], [ 'response_api' => $result, 'result' => $db_res ] );
        echo $result;
    }

    public function net( $ip = false )
    {
        $this->load->model( 'main_model' );

        $black_ip = $this->main_model->getAll( 'black_ip' );

        foreach ( $black_ip as $black_ip ) {
            $ip     = $black_ip[ 'ip' ];
            $db_net = $this->main_model->getAll( 'black_net' );
            foreach ( $db_net as $net ) {
                $mas       = explode( "/", $net[ 'net' ] );
                $check_net = $this->main_model->net_search( $ip, $mas[ 0 ], $mas[ 1 ] );
                if ( $check_net ) {
                    $this->main_model->delete( 'black_ip', [ 'ip' => $ip ] );
                } else {
                }
            }
        }
    }

    public function img_tracker()
    {
        $this->load->model( 'main_model' );
        $log_id = $this->input->post( 'log_id' );
        $this->main_model->update( 'logs', [ 'id' => $log_id ], [ 'img_tracker' => 1 ] );
    }

    public function update_status_domains()
    {
        $this->load->model( 'main_model' );
        $domains = $this->main_model->getAll( 'domains' );

        foreach ( $domains as $domain ) {
            $check_ban = $this->main_model->check_block_domain( $domain[ 'domain' ] );
            if ( $check_ban ) {
                $this->main_model->update( 'domains', [ 'id' => $domain[ 'id' ] ], [ 'status' => 1 ] );
            } else {
                $this->main_model->update( 'domains', [ 'id' => $domain[ 'id' ] ], [ 'status' => 0 ] );
            }

            $check_serv_ip = @dns_get_record( $domain[ 'domain' ], DNS_A );
            $serv_ip       = $check_serv_ip[ 0 ][ 'ip' ];
            $this->main_model->update( 'domains', [ 'id' => $domain[ 'id' ] ], [ 'status_s' => $serv_ip ] );
        }
    }

}