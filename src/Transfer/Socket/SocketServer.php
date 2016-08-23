<?php
namespace Kraken\Transfer\Socket;

use Kraken\Ipc\Socket\SocketInterface;
use Kraken\Ipc\Socket\SocketListenerInterface;
use Kraken\Transfer\Null\NullServer;
use Kraken\Transfer\TransferComponentAwareInterface;
use Kraken\Transfer\TransferMessage;
use Kraken\Transfer\TransferComponentInterface;
use Error;
use Exception;

class SocketServer implements SocketServerInterface, TransferComponentAwareInterface
{
    /**
     * @var SocketListenerInterface
     */
    protected $socket;

    /**
     * @var TransferComponentInterface
     */
    protected $component;

    /**
     * @param TransferComponentInterface $component
     * @param SocketListenerInterface $socket
     */
    public function __construct(SocketListenerInterface $socket, TransferComponentInterface $component = null)
    {

        $this->socket = $socket;
        $this->component = $component === null ? new NullServer() : $component;

        $socket->on('connect', [ $this, 'handleConnect' ]);
    }

    /**
     *
     */
    public function __destruct()
    {
        unset($this->socket);
        unset($this->component);
    }

    /**
     * @override
     * @inheritDoc
     */
    public function setComponent(TransferComponentInterface $component = null)
    {
        $this->component = $component === null ? new NullServer() : $component;
    }

    /**
     * @override
     * @inheritDoc
     */
    public function getComponent()
    {
        return $this->component;
    }

    /**
     * Handler triggered when a new connection is received from SocketListener.
     *
     * @param SocketListenerInterface $server
     * @param SocketInterface $socket
     */
    public function handleConnect($server, $socket)
    {
        $socket->conn = new SocketConnection($socket);

        try
        {
            $this->component->handleConnect($socket->conn);

            $socket->on('data',  [ $this, 'handleData' ]);
            $socket->on('error', [ $this, 'handleError' ]);
            $socket->on('close', [ $this, 'handleDisconnect' ]);
        }
        catch (Error $ex)
        {
            $this->close($socket);
        }
        catch (Exception $ex)
        {
            $this->close($socket);
        }
    }

    /**
     * Handler triggered when an existing connection is being closed.
     *
     * @param SocketInterface $socket
     */
    public function handleDisconnect($socket)
    {
        try
        {
            $this->component->handleDisconnect($socket->conn);
        }
        catch (Error $ex)
        {
            $this->handleError($socket, $ex);
        }
        catch (Exception $ex)
        {
            $this->handleError($socket, $ex);
        }

        unset($socket->conn);
    }

    /**
     * Handler triggered when a new data is received from existing connection.
     *
     * @param SocketInterface $socket
     * @param mixed $data
     */
    public function handleData($socket, $data)
    {
        try
        {
            $this->component->handleMessage($socket->conn, new TransferMessage($data));
        }
        catch (Error $ex)
        {
            $this->handleError($socket, $ex);
        }
        catch (Exception $ex)
        {
            $this->handleError($socket, $ex);
        }
    }

    /**
     * Handler triggered when an error has occured during doing operation on existing connection.
     *
     * @param SocketInterface $socket
     * @param Error|Exception $ex
     */
    public function handleError($socket, $ex)
    {
        try
        {
            $this->component->handleError($socket->conn, $ex);
        }
        catch (Error $ex)
        {
            $this->close($socket);
        }
        catch (Exception $ex)
        {
            $this->close($socket);
        }
    }

    /**
     * Close socket.
     *
     * @param SocketInterface $socket
     */
    protected function close(SocketInterface $socket)
    {
        $socket->close();
    }
}
