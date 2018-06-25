<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 
 */
class Ssh
{
	private $_ip;
	private $_port;
	private $_username;
	private $_password;
	private $_connection;
	private $_connected = FALSE;



	public function __construct($config) 
	{

		if (!function_exists('ssh2_connect')) {
            throw new Exception('<p class="lead text-danger text-center">FATAL: ssh2_connect function doesn\'t exist!</p>');
        }
        if(!$this->isServerRunning($config['ip'],$config['port'])) {   
            throw new Exception('<p class="lead text-danger text-center">Server Down</p>');
        }
        //init
        if(is_array($config)) {
        	$this->_ip = $config['ip'];
			$this->_username = $config['username'];
			$this->_password = $config['password'];
			$this->_port = $config['port'];
        } else {
        	 throw new Exception('<p class="lead text-danger text-center">Invalid Argument</p>');
        }
		
	}

	public function Connect()
	{
		$this->_connection = ssh2_connect($this->_ip, $this->_port);
		if(!$this->_connection)

			throw new Exception('<p class="lead text-danger text-center">Cannot connect to server</p>');
		else 
		{
			if(!@ssh2_auth_password($this->_connection, $this->_username, $this->_password))
				return array('status' => FALSE, 'msg' => 'Authentication Failed');
			else
			{
				$this->_connected = TRUE;
				return array('status' => TRUE, 'msg' => 'Connected');
			}
		}
	}
	public function command($cmd)
	{
		if($this->_connected)
		{
			$stream = ssh2_exec($this->_connection, $cmd);
			$streamError = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
			stream_set_blocking($stream, true);
			stream_set_blocking($streamError, true);
			return array('status' => TRUE, 'output' => stream_get_contents($stream), 'error' => stream_get_contents($streamError));
		}
		else
		{
			return array('status' => FALSE, 'msg' => 'NOT CONNECTED TRY CONNECT!');
		}

	}
	public function scp($cmd, $localFile, $remoteFile, $permission = 0755)
	{
		if($this->_connected)
		{
			$function = 'ssh2_scp_' . $cmd;
	    	switch ($function) {

	    		case 'ssh2_scp_send':
	    			//Copy a file from the local filesystem to the remote server using the SCP protocol.
	    			//upload file to your machine
	    			return ssh2_scp_send($this->_connection, $localFile, $remoteFile, $permission) ? array('status' => TRUE, 'msg' => 'File copied successfully') : array('status' => FALSE, 'msg' => 'Copying file failed') ;
	    		break;
	    		case 'ssh2_scp_recv':
	    			//Copy a file from the remote server to the local filesystem using the SCP protocol.
	    			//download file to your machine
	    			return ssh2_scp_recv($this->_connection, $remoteFile, $localFile) ? array('status' => TRUE, 'msg' => 'File downloaded successfully') : array('status' => FALSE, 'msg' => 'Downloading file failed') ;
	    		break;
	    		default:
	    			throw new Exception(array('status' => FALSE, 'msg' => $function . ' is not a valid SCP function'));
	    			break;
	    	}
		}
		else
		{
			return array('status' => FALSE, 'msg' => 'NOT CONNECTED TRY CONNECT!');
		}
	}
	public function sftp($cmd,$parameter) 
    {
    	if($this->_connected)
		{
	        $sftp = ssh2_ftp($this->_connection);
	        $function = 'ssh2_sftp_' . $cmd;
	    	switch ($function) {
	    		case 'ssh2_sftp_unlink':
	    			//Deletes a file on the remote filesystem.
	    			return ssh2_sftp_unlink($sftp, $parameter['path']) ? array('status' => TRUE, 'msg' => 'File Deleted') : array('status' => FALSE, 'msg' => 'File Not Deleted') ;
	    		break;
	    		case 'ssh2_sftp_chmod':
	    			//Attempts to change the mode of the specified file to that given in mode.	
	    			//change permission
	    			return ssh2_sftp_chmod ($sftp, $parameter['path'], $parameter['permission']) ? array('status' => TRUE, 'msg' => 'Permissions on the file changed') : array('status' => FALSE, 'msg' => 'Failed updating permission') ;
	    		break;
	    		case 'ssh2_sftp_stat':
	    			//Stats a file on the remote filesystem following any symbolic links.
	    			//file info size gid ...
	    			return ssh2_sftp_stat($sftp, $parameter['path']);
	    		break;
	    		case 'ssh2_sftp_lstat':
	    			//Stats a symbolic link on the remote filesystem without following the link.
	    			// file info with symbolic link
	    			return ssh2_sftp_lstat($sftp, $parameter['path']);
	    		break;
	    		case 'ssh2_sftp_mkdir':
	    			//Creates a directory on the remote file server with permissions set to mode.
	    			return ssh2_sftp_mkdir($sftp, $parameter['path']) ? array('status' => TRUE, 'msg' => 'DIR Created') : array('status' => FALSE, 'msg' => 'Failed creating DIR') ;
	    		break;
	    		case 'ssh2_sftp_readlink':
	    			//Returns the target of a symbolic link.
	    			return ssh2_sftp_readlink($sftp, $parameter['path']); 
	    		break;
	    		case 'ssh2_sftp_realpath': 
	    			//ssh2_sftp_realpath — Resolve the realpath of a provided path string
	    			return ssh2_sftp_realpath($sftp, $parameter['path']);
	    		break;
	    		case 'ssh2_sftp_rename':
	    			//Renames a file on the remote filesystem.
	    			return ssh2_sftp_rename($sftp, $parameter['oldName'], $parameter['newName']) ? array('status' => TRUE, 'msg' => 'Renamed') : array('status' => FALSE, 'msg' => 'Failed Renaming') ;
	    		break;
	    		case 'ssh2_sftp_rmdir':
	    			//removes a directory from the remote file server.
	    			return ssh2_sftp_rmdir($sftp, $parameter['path']) ? array('status' => TRUE, 'msg' => 'DIR Removed') : array('status' => FALSE, 'msg' => 'Failed Deliting DIR') ;
	    		break;
	    		case 'ssh2_sftp_symlink':
	    			//Creates a symbolic link named link on the remote filesystem pointing to target.
	    			return ssh2_sftp_symlink($sftp, $parameter['target'], $parameter['link']) ? array('status' => TRUE, 'msg' => 'Symbolic link created') : array('status' => FALSE, 'msg' => 'Failed Creating Symbolic link') ;
	    		break;
	    		default:
	    			throw new Exception(array('status' => FALSE, 'msg' => $function . ' is not a valid SFTP function or not supported'));
	    			break;
	    	}
    	}
		else
		{
			return array('status' => FALSE, 'msg' => 'NOT CONNECTED TRY CONNECT!');
		}
    }
	public function disconnect() 
	{
		if($this->_connected) {
			ssh2_exec($this->_connection, 'exit');
			$this->_connected = FALSE;
		}
	}
	private function isServerRunning($ip,$port) 
    { 
        $running = false;
        $fp = @fsockopen($ip,$port,$errCode,$errStr,5);
        if($fp)
        {   
            $running = true;
            fclose($fp);
        }
        return $running;
    }
    public function __destruct() 
	{
		$this->disconnect();
	}

}
