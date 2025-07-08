<?php
namespace App\Services;
use App\Models\Auth\User;
use Kreait\Firebase\Factory;
class FirestoreService
{
    protected $db;
    public function __construct()
    {
         $factory =(new Factory) ->withServiceAccount(config('firebase.credential'));
         $firestore =$factory->createFirestore();
         $this->db = $firestore->database();
    }

        public function sendToAllUsers(array $data)
    {
        $users = User::pluck('id'); 

        foreach ($users as $userId) {
            $this->send($userId, $data);
        }
    }


    public function send ($reciever_id,$data){
        $this ->db->collection('notifications')
        ->document($reciever_id)
        ->collection('user_notifications')
        ->add([
            'title' => $data['title'],
            'body' => $data['body'],
            'type' => $data['type'],
            'created_at' => now(),
            'is_read' => false,
        ]);

    }
}