<?php

if ( ! defined( '__SHAFI_FOLDER' ) ) {
    exit; // Exit if accessed directly
}

class SHAFI_Op_Edit extends SHAFI_Op_File {
    protected $op = 'edit';
    const PERMS=[ 'edit' ];

    public function _do() {
        $this->clear_messages();

        if (isset($_POST['reactivate'])) {
            if ($this->file->reactivate(true)) 
                // Now we need to re-update the state
                return $this->add_success_message(__('The file has been successfully activated'));
            else
                return $this->add_error_message(__('The file could not be activated'));
        }

        // If the file is not active, we cannot perform any further operation
        if (! $this->file->is_active())
            return false;

        if (isset($_POST['cancelall'])) {
            $tokens = $this->file->get_active_tokens();
            if (sizeof($tokens) == 0) 
                return $this->add_error_message(__('No token to cancel'));

            $failed = 0;
            foreach ($tokens as $token)
                if ($token->cancel(true) !== true) $failed++;

            if ($failed > 0)
                return $this->add_error_message(_s('Some tokens (%d) could not be cancelled', $failed));

            return $this->add_success_message(__('Tokens successfully cancelled'));
        }

        if (isset($_POST['create'])) {
            // Get the values for the expiration times
            $result = $this->_read_hits_and_seconds_from_post();
            if ($result === false) return false;

            [$hits, $seconds, $passwd] = $result;

            if ((!__ALLOW_INFINITE_TOKENS) && ($seconds === null) && ($hits === null)) 
                return $this->add_error_message(__('Infinite tokens are not permitted'));

            $token = $this->file->create_token($seconds, $hits, $passwd);
            if (! $token->create()) 
                return $this->add_error_message(__('Failed to create token'));

            // Update the state (just in case that changes from grace to alive)
            $this->file->update_state(true);
            return true;
        }   
        
        if (isset($_POST['renew'])) {
            // Get the values for the expiration times
            $result = $this->_read_hits_and_seconds_from_post();

            if ($result === false) return false;
            [$hits, $seconds, $passwd] = $result;

            $token = $this->file->get_tokens(['id' => $_POST['token']]);
            if (sizeof($token) !== 1) 
                return $this->add_error_message(__('Invalid token'));

            $token = $token[0];

            if ((!__ALLOW_INFINITE_TOKENS) && ($seconds === null) && ($hits === null))
                return $this->add_error_message(__('Infinite tokens are not permitted'));

            $other_tokens = $this->file->get_tokens([ 'oid' => $token->get_field('oid') /*, '!id' => $token->get_id() */ ]);
            foreach ($other_tokens as $o_token) {
                // There should be only one token with the same oid alive, so we are cancelling any alive token
                if ($o_token->get_id() !== $token->get_id())
                    $o_token->cancel(true);
            }
            
            if ($o_token->is_active()) {
                // If the token was active, let's change the expiration values
                if ($seconds === null) {
                    // Means that will remove the time expiration
                } else {
                    // We are setting the expiration time from "now"
                    $now = new Datetime();
                    $seconds = $now->getTimestamp() - $token->get_field('time')->getTimestamp() + $seconds;
                }

                $token->set_limits($seconds, $hits);
                $token->set_password($passwd);
                if (! $token->save_limits_and_password()) {
                    $this->add_message(new DDN_Notice_Error('Fallo al crear el token'));
                    return false;
                }
            } else {
                // If the token to be renewed was not active, let's create a new one
                $token_renewed = $this->file->create_token($seconds, $hits, $passwd);
                $token_renewed->set_field('oid', $token->get_field('oid'));
                if (! $token_renewed->create()) 
                    return $this->add_error_message(__('Failed to create token'));

                // Update the state (just in case that changes from grace to alive)
                $this->file->update_state(true);
            }
            return true;
        } 

        if (isset($_POST['cancel'])) {
            $token = $this->file->get_active_tokens(['id' => $_POST['token']]);
            if (sizeof($token) !== 1) 
                return $this->add_error_message(__('Token not corresponds to the file'));

            $token = $token[0];
            if (! $token->cancel(true)) 
                return $this->add_error_message(__('Failed to cancel token'));

            // Update the state (just in case that changes from grace to alive)
            $this->file->update_state(true);
            return true;
        }
        return false;
    }        
}

