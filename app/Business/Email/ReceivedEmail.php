<?php 
namespace App\Business\Email;

use App\Business\Business;
use App\Http\Controllers\UserEmailController;
use Illuminate\Support\Facades\DB;
use App\Models\ReceiveMessages;

class ReceivedEmail
{
    use Business;

    public function index(){
        $this->storeEmails();
        return ReceiveMessages::orderby('id', 'DESC')->get();
    }

    public function show($request){
        return $this->filterReceived($request);
    }

    private function storeEmails(){ 
        $user = new UserEmailController($this->username, $this->password);
        $folders = $user->getClient()->getFolders();
        foreach($folders as $folder) {
            $query = $folder->messages();
            $uids = $user->getClient()->getConnection()->search(["ALL"], $query->getSequence());
            foreach ($uids as $uid) {
                if (DB::table('receive_messages')->where('email_id_message', $uid)->first() == NULL){
                    $message = $query->getMessageByUid($uid);
                    $attributes = $message->getAttributes();
                    $received['email_id_message'] = $attributes['uid'];
                    $received['subject_message'] = $message->getSubject();
                    $received['from_mail_message'] = $attributes['from']->values[0]->mail;
                    $received['from_fullname_message'] = $attributes['from']->values[0]->personal;
                    $received['html_message'] = $message->getHTMLBody();
                    $received['folder_message'] = $folder->path;
                    $received['received_message'] = $message->getDate()->toString();
                    DB::table('receive_messages')->insert($received);    
                }
            }
        }
    }

    private function filterReceived($request){
        $filtros = $request->only('data', 'remetente', 'conteudo');
        $data = [
            'received_message' => $filtros['data'] ?? '' ,
            'from_fullname_message' => $filtros['remetente'] ?? '',
            'html_message' => $filtros['conteudo'] ?? '' 
        
        ];
        return $this->getByFilter('receive_messages', $data);  
        /*switch($filtros) {
            case 'data':
                $column = 'received_message';
                break;
                case 'remetente':
                $column = 'from_fullname_message';
                break;
            case 'conteudo':
                $column = 'html_message';
                break;
            default:
                echo "Tipo de filtro não encontrado";
        }*/
        
    }
    
    /*public function getByFilter($table, $filtros){
        foreach ($filtros as $key => $value){
            if ($key == 'data'){
                return DB::table($table)->where('received_message', 'LIKE', "%{$value}%")->exists();
            }
        }
    }*/

    public function getByFilter($table, $data){
        $query = '';
        $i = 0;
        foreach ($data as $key => $value){
            if ($value != ''){
                $where = ($i==0) ? 'WHERE' : 'AND';
                $query .= " $where ".$key." LIKE '".$value."%'";
                $i++;
            }
            
        }
        return DB::select('select * from ' . $table . '' .$query);
        
    }
    
    private $username = 'caio.magalhaes@construsitebrasil.com.br';
    private $password = '01052003Cc@';

}
