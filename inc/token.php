<?php

if ( ! defined( '__SHAFI_FOLDER' ) ) {
    exit; // Exit if accessed directly
}

require_once(__SHAFI_INC . 'dbobject.php');
require_once(__SHAFI_INC . 'uuid.php');
require_once(__SHAFI_INC . 'storage.php');
require_once(__SHAFI_INC . 'file.php');

class SHAHit extends SCPM_DBObject {
    // TODO: decide whether to implement this or not
    protected static $db_tablename = 'hits';
    const FIELDS = [
        'tokenid' => 'int',
        'ip',
        'referer'
    ];
}

class SHAToken extends SCPM_DBObject {
        protected static $db_tablename = 'tokens';
        const FIELDS = [
            'oid',
            'password',
            'time' => 'datetime',       // When it was created
            'exp_secs' => 'int',      // Expiration units when created
            'exp_hits' => 'int',
            'fileid',          // The refered file
            'expired' => 'datetime',    // When it expired (NULL means that it has not expired)
            'state',                    // The state of expiration (alive, expired, cancelled, etc.)
            'hits' => 'int'
        ];

        protected $oid = null;
        protected $time = null;
        protected $exp_secs = null;
        protected $exp_hits = null;
        protected $fileid = null;
        protected $expired = null;
        protected $state = 'a';
        protected $hits = 0;
        protected $password = null;

        public function cancel($autosave = false) {
            if ($this->is_active()) {
                $now = new Datetime();
                $this->expired = $now;
                $this->state = 'c';

                if ($autosave) {
                    if (! $this->save_i(['expired', 'state'])) {
                        global $pagecomm;
                        $pagecomm->add_message('error', __('Token could not be cancelled'));
                        return false;
                    }
                }
                return true;
            }
            return false;
        }

        public function is_deleted() {
            return $this->state == 'd';
        }

        public function is_active() {
            return in_array($this->state, ['a', 'g']);
        }

        public function expiration_check($autosave = false) {
            $now = new Datetime();
            if (! $this->is_active()) {

                // This is a consistency check... If a token is not active but it is not marked as expired, it will be marked as expired as of "now"
                //  this should not happen, but the database may be changed by hand
                if ($this->expired === null) {
                    $this->expired = $now;
                    if ($autosave)
                        $this->save_i(['expired', 'state']);
                }
                return false;
            }

            $prev_state = $this->state;
            if ($this->is_active())
                if ($this->exp_secs !== null) {
                    $seconds = $now->getTimestamp() - $this->time->getTimestamp();
                    if ($seconds > $this->exp_secs) {
                        // It is marked as expired when it should expire
                        $this->expired = $this->time->add(new DateInterval('PT' . $this->exp_secs . 'S'));
                        $this->state = 'e';
                    }
                }

            // We check again to not to expire it again if both things happen
            if ($this->is_active())
                if ($this->exp_hits !== null) {
                    if ($this->hits >= $this->exp_hits) {
                        // We noticed that the amount of seconds is now
                        $this->expired = $now;
                        $this->state = 'e';
                    }
                }

            if ($this->state != $prev_state) {
                if ($autosave) {
                    if (! $this->save_i(['expired', 'state'])) {
                        global $pagecomm;
                        $pagecomm->add_message('error', __('Token has expired but the state could not be saved'));
                        return false;
                    }
                }
                return true;
            }
            return false;
        }

        public function __construct($id = null) {
            parent::__construct($id);
            $this->oid = UUID::v4();
            $this->time = new Datetime();
        }

        public function set_limits($seconds, $hits) {
            $this->exp_secs = $seconds;
            $this->exp_hits = $hits;
        }

        public function set_password($password) {
            if ($password === null)
                $this->password = null;
            else
                $this->password = password_hash($password, PASSWORD_BCRYPT);
        }

        public function save_limits_and_password() {
            return $this->save_i(['exp_secs', 'exp_hits', 'password']);
        }

        public function add_hit($autosave = false) {
            $this->hits++;
            if ($autosave) 
                $this->save_i(['hits']);
        }

        public function set_fileid($fileid) {
            $this->fileid = $fileid;
        }
    }

