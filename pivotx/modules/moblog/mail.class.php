<?php
/* $Id: mail.class.php 35 2004-11-12 21:14:32Z eroberts $ */
/**
 * @package Framework
 * @subpackage Classes
 */
/**
 * New Mail class for retrieving mail from a POP3 or IMAP server
 *
 * @author Edwin Robertson {TuxMonkey}
 * @version 0.1
 * @package Framework
 * @subpackage Classes
 */
class Mail {
  /** Connection to mail server */
  var $conn = null;
  /** Mail server */
  var $server = null;
  /** Mail server type */
  var $server_type = null;
  /** Number of messages in mailbox */
  var $num_msgs = 0;
  /** Array of data regarding incoming messages */
  var $messages = null;
  /** Regex to verify an email address */
  var $regex;

  /** Constructor */
  function Mail()
  {
    $this->regex = "/^[_.0-9a-z-]+@[0-9a-z]+\.[a-z]{2,4}$/i";
  }

  /**
   * Make a connect with a imap or pop3 mail server
   *
   * @param array $params Parameters for making connection
   */
  function connect($params)
  {
    # Storing server type
    $server_type = $params['type'];

    # Determine port to use
    $port = ($params['type'] == "pop3")
      ? ($params['secure'] ? 995 : 110)
      : ($params['secure'] ? 993 : 143);

    # Form server string
    $server  = "{".$params['server'].":$port/".$params['type'];
    $server .= $params['secure'] ? "/ssl" : "";
    $server .= "}";

    # Attempt connection
    $this->conn = @imap_open($server.$params['mailbox'],$params['username'],$params['password']);
    # If failure also try with "notls" and "novalidate-cert" option
    if (!$this->conn) {
      $server = str_replace("}","/notls}",$server);
      $this->conn = @imap_open($server.$params['mailbox'],$params['username'],$params['password']);
    }
    if (!$this->conn) {
      $server = str_replace("/notls}","/novalidate-cert}",$server);
      $this->conn = @imap_open($server.$params['mailbox'],$params['username'],$params['password']);
    }
   
    
    # Connection made
    if ($this->conn) {
      # Keep track of server string
      $this->server = $server;
      # Retrieve number of messages in mailbox
      $this->num_msgs = imap_num_msg($this->conn);
      return true;
    } else {
      # If connection not made then log error
      return false;
    }
  }

  /** Parse messages sitting in mailbox */
  function parse_messages()
  {
    if ($this->num_msgs > 0) {
      for ($x = 1;$x < ($this->num_msgs + 1);$x++) {
        # Retrieve raw mail body
	$rawdata = imap_fetchheader($this->conn,$x).imap_body($this->conn,$x);
        # Retrieve mail structure
        $struct = imap_fetchstructure($this->conn,$x);
        # Retrieve mail headers
        $headers = imap_headerinfo($this->conn,$x);

        # Build array of addresses mail was sent to
        $to = array();
        foreach ($headers->to as $item) {
          array_push($to,$item->mailbox."@".$item->host);
        }

        # Get the address message is from
        $from = $headers->from[0]->mailbox."@".$headers->from[0]->host;

	# FIXME - attachment handling:
	# Use of dparameters seems to be wrong - the correct key is 'filename' I guess.
	# More info http://php.net/manual/en/function.imap-fetchstructure.php
	# Anyway, I have removed the attachment code since it doesn't work AND
	# the code in the parser script already handle the attachments.
	
    # Check if this is a multipart message. (can not use type since
	# it is 0 for text (.txt) attachments.)
        // if ($struct->type == 1) {
        if ($struct->parts) {
          
          foreach ($struct->parts as $key => $part) {
	    // Skipping HTML
            if ($part->ifsubtype == 1 and $part->subtype == "HTML") {
              continue;
            }
            // Ignoring all attachements 
            if (strtolower($part->disposition) != "attachment") {
              # Retrieve mail body
              $body = imap_fetchbody($this->conn,$x,$key + 1);

              # Check for base64 or quoted printable encoding
              if ($part->encoding == 3) {
                $body = imap_base64($body);
              } else if ($part->encoding == 4) {
                $body = imap_qprint($body);
              }
            }
          }
        } else {
          # Retrieve mail body (for this single part message).
          $body = imap_body($this->conn,$x);

          # Check for base64 or quoted printable encoding
          if ($struct->encoding == 3) {
            $body = imap_base64($body);
          } else if ($struct->encoding == 4) {
            $body = imap_qprint($body);
          }
        }

        # Add message to array
        $this->messages[] = array(
          'msgno'       => $x,
          'from'        => $from,
          'to'          => $to,
          'message_id'  => $headers->message_id,
          'subject'     => $headers->subject,
          'body'        => $body,
          'rawdata'     => $rawdata,
        );
      }
    }
  }

  /**
	 * Send a message
	 *
	 * Message hash structure:
	 * array(
	 *   "to" => "<address to send message to>",
	 *   "from" => "<address message originates from>",
	 *   "subject" => "<message subject>",
	 *   "body" => "<message body>",
	 *   "headers => array(
	 *     <any additional headers>
	 *   )
	 * )
	 *
	 * @param array $message Message hash
   */
  function send(&$message)
  {
		/*
		if (empty($message['from']) or empty($message['to'])
		or empty($message['body'])
		or !preg_match($this->regex,$message['from'])
		or $GLOBALS['config']['debug_mode'] === TRUE) {
			return FALSE;
		}
		*/

		# Form headers array
		$headers  = "From: ".$message['from']."\n";
		if (is_array($message['headers'])) {
			$headers .= join("\n",$message['headers']);
		}
		
    # Send message
		if (is_array($message['to'])) {
			foreach ($message['to'] as $to) {
				if (preg_match($this->regex,$to)) {
					mail($to,$message['subject'],$message['body'],$headers,"-f{$message['from']}");
				}
			}
		} else {
			if (preg_match($this->regex,$message['to'])) {
				mail($message['to'],$message['subject'],$message['body'],$headers,"-f{$message['from']}");
			}
		}
  }

  /**
   * Delete message
   *
   * @param integer $msgno Message number to delete
   * @return boolean
   */
  function delete($msgno)
  {
    return imap_delete($this->conn,$msgno);
  }

  /**
   * Move message to another mailbox
   *
   * @param integer $msgno Message number to move
   * @param string $mbox Mailbox to move message to
   * @return boolean
   */
  function move($msgno,$mbox)
  { // Only imap supports moving of mesages
    if ($server_type == "imap") {
      $list = imap_list($this->conn,$this->server,"*");
      if (!in_array($mbox,$list)) {
        if (!imap_createmailbox($this->conn,imap_utf7_encode($this->server.$mbox))) {
          // $_ENV['api']['sys']->log("Creation of $mbox mailbox failed!","mailer");
        }
      }
      return imap_mail_move($this->conn,$msgno,$mbox);
    } else {
      return imap_delete($this->conn,$msgno);
    }
  }

  /** Close connection to mail server */
  function close()
  {
    if ($this->conn) {
      # Cleanup mailbox and close connection
      imap_close($this->conn,CL_EXPUNGE);
    }
  }
}
?>
