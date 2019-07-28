<?php /** @noinspection ALL */


namespace App\controller;


use App\auth\Authentication;
use App\common\AppConstant;
use App\common\utils\Validator;
use App\data\model\Users;
use App\ServiceContainer;
use Exception;

class UsersController extends BaseController
{
    private $model;

    /**
     * UsersController constructor.
     * @throws Exception
     */
    public function __construct()
    {
        $this->model = ServiceContainer::inject()->get(AppConstant::IOC_USER_MODEL);
    }

    function index()
    {
        // TODO: Implement index() method.
    }

    function show($id)
    {
        // TODO: Implement show() method.
    }

    function create()
    {
        // TODO: Implement create() method.
    }

    function update($id)
    {
        // TODO: Implement update() method.
    }

    function delete($id)
    {
        // TODO: Implement delete() method.
    }

    /**
     * @param $credentials
     */
    function authenticateUser($credentials)
    {
        $username = $credentials['username'] ?? null;
        $password = $credentials['password'] ?? null;
        $userType = $credentials['user'] ?? null;
        if (empty($username) || empty($password)) {
            http_response_code(400);
            echo json_encode(array("status" => 400, "message" => "field can't be empty"));
            return;
        }
        $this->model->output['status'] = 401;
        $this->model->output['message'] = 'email or password incorrect';
        $this->model->output['token'] = null;
        $check_username = null;
        switch ($userType) {
            case 'parent':
                $check_username = $this->model->verifyParentUsername($username, $password, 'student');
                $this->prepareToAuthenticate($check_username, $password, $this->model);
                break;

            case 'teacher':
                $check_username = $this->model->verifyTeacherUsername($username, 'teachers');
                $this->prepareToAuthenticate($check_username, $password, $this->model);
                break;
            default:
                null;
        }

    }

    /**
     * @param $checkUsername
     * @param $password
     * @param Users $model
     */
    private function prepareToAuthenticate($checkUsername, $password, Users $model)
    {
        if ($checkUsername && ($password == $model::$password)) {
            $jwt = array(
                "iss" => Authentication::$jwt_package['iss'],
                "aud" => Authentication::$jwt_package['aud'],
                "iat" => Authentication::$jwt_package['iat'],
                "nbf" => Authentication::$jwt_package['nbf'],
                "key" => getenv('JWT_KEY')
            );

            $user_data = array(
                "id" => $model->id,
                "full_name" => $model::$full_name,
                "username" => $model::$user_name,
                "image" => $model::$image,
                "level" => $model::$level
            );
            $model->output['status'] = 200;
            $model->output['message'] = 'Login Successful';
            $model->output['uuid'] = $model->id;
            $model->output['imageUrl'] = $model::$image;
            $model->output['token'] = Authentication::encodeJWTToken($jwt, $user_data);
            echo json_encode($model->output);

        } else {
            http_response_code(401);
            echo json_encode($model->output);
        }
    }

    function sendMessage(array $messageContent)
    {
        $deviceId = $messageContent['device'];
        $title = $messageContent['title'];
        $content = $messageContent['message'];
        $field = ['deviceId', 'title', 'content'];
        $input = [$deviceId, $title, $content];
        if (!Validator::validateInput($field, $input)) {
            return;
        }
        $fcmBody = [
            "to" => $deviceId,
            "notification" => [
                "body" => $content,
                "title" => $title
            ]
        ];
        $fcmBody = json_encode($fcmBody);
        $headers = [
            'Content-Type: application/json',
            "Authorization: key=" . getenv('FCM_KEY')
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, AppConstant::FIREBASE_MESSAGING_URL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fcmBody);
        $results = curl_exec($ch);
        curl_close($ch);
        echo $results;
    }
}