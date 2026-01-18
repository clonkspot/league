<?php

// Dummy MwfAuth for testing.
// Allows login with admin / admin

class MwfUser
{
    // Identification types
    const IdentName = 1;
    const IdentId = 2;

    private $user_row = NULL;
    private $user_name = NULL;

    public function __construct($ident, $type = MwfUser::IdentName)
    {
        // Only handle the admin user
        if ($type === MwfUser::IdentName && $ident === 'admin') {
            // Create a dummy user row for admin
            $this->user_row = array(
                'id' => 1,
                'userName' => 'admin',
                'admin' => 1,
                'salt' => 'dummy_salt',
                'password' => $this->hash_password('admin', 'dummy_salt'),
                'loginAuth' => 'dummy_cookie_secret',
                'language' => 'en',
                'timezone' => 'UTC',
                'email' => 'admin@example.com',
                'icq' => '',
                'realName' => 'Administrator',
                'title' => 'Admin',
                'homepage' => '',
                'occupation' => '',
                'hobbies' => '',
                'location' => '',
                'avatar' => '',
                'signature' => '',
                'birthyear' => '0',
                'birthday' => '0000-00-00',
                'extra1' => '',
                'extra2' => '',
                'extra3' => ''
            );
            $this->user_name = 'admin';
        } else {
            // For all other users, leave user_row as NULL to indicate non-existence
            $this->user_row = NULL;
            $this->user_name = NULL;
        }
    }

    /** Hash the password the same way mwForum does it */
    private static function hash_password($password, $salt)
    {
        $data = $password . $salt;
        $rounds = 100000;

        for($i = 0; $i < 100000; ++$i)
        {
            $data = md5($data, true);
        }

        $data = base64_encode($data);
        $data = strtr($data, array('+'=>'-','/'=>'_'));

        // remove the trailing ==
        if(substr($data, -2) == '==')
        {
            $data = substr($data, 0, -2);
        }

        return $data;
    }

    /** Whether the user exists at all */
    public function exists()
    {
        return !!$this->user_row;
    }

    /** Authenticate the user. Returns false if user does not exist or password is wrong */
    public function authenticate($password)
    {
        // user does not exist:
        if(!$this->exists())
        {
            return false;
        }

        if(!isset($password) || !$password)
        {
            return false;
        }

        $password_hash = self::hash_password($password, $this->user_row['salt']);
        if ($password_hash != $this->user_row['password'])
        {
            return false;
        }

        return true;
    }

    /** Check the given cookie secret for the user. */
    public function cookie_authenticate($cookie_secret)
    {
        if(!$this->exists())
        {
            return false;
        }
        return $this->user_row['loginAuth'] === $cookie_secret;
    }

    public function get_user_name()
    {
        return $this->user_name;
    }

    /** Get info from a user. Returns NULL if the user does not exist, otherwise an associative array */
    function get_info()
    {
        if(!$this->exists())
        {
            return NULL;
        }

        $result = array(
            'id'               => $this->user_row['id'],
            'userName'         => $this->user_row['userName'],
            'admin'            => ($this->user_row['admin'] == 1),

            // language
            'language'         => $this->user_row['language'],
            'timezone'         => $this->user_row['timezone'],

            // contact
            'email'            => $this->user_row['email'],
            'instantMessenger' => $this->user_row['icq'],

            // profile
            'realName'         => $this->user_row['realName'],
            'title'            => $this->user_row['title'],
            'homepage'         => $this->user_row['homepage'],

            'occupation'       => $this->user_row['occupation'],
            'hobbies'          => $this->user_row['hobbies'],
            'location'         => $this->user_row['location'],

            'avatar'           => $this->user_row['avatar'],
            'signature'        => $this->user_row['signature'],
            'birthyear'        => $this->user_row['birthyear'],
            'birthday'         => $this->user_row['birthday'],

            // mwforum extra data
            'extra1'           => $this->user_row['extra1'],
            'extra2'           => $this->user_row['extra2'],
            'extra3'           => $this->user_row['extra3']
        );

        return $result;
    }

    /** Get groups of a user. Returns NULL if the user does not exist, otherwise an array of groups */
    function get_groups()
    {
        if(!$this->exists())
        {
            return NULL;
        }

        // Return a dummy list of groups for admin
        return array('Administrators', 'Users');
    }
}
?>
