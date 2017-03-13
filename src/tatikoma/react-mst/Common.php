<?php
namespace Tatikoma\React\MicroServiceTransport;
class Common{
    /**
     * Total length of packet header
     */
    const HEADER_LENGTH = 20;

    // header format: Length 4b, Sequence 4b, Connection 4b, Round Factor 8b
    /**
     * @param string $data
     * @param array $options
     * @return string packed data
     */
    static public function writeHeader($data, array $options = []){
        return pack('NNNJ',
            strlen($data) + self::HEADER_LENGTH,
            $options['sequence'] ?? 0,
            $options['connection'] ?? 0,
            $options['factor'] ?? 0
        ) . $data;
    }

    /**
     * @param string $data
     * @return array headers
     */
    static public function readHeader($data){
        $header = unpack('Nlength/Nsequence/Nconnection/Jfactor', substr($data, 0, self::HEADER_LENGTH));
        return array_merge($header, [
            'data' => substr($data, self::HEADER_LENGTH),
        ]);
    }

    /**
     * @param string $data
     * @return int
     */
    static public function readLength($data){
        $unpack = unpack('Nlength', substr($data, 0, 4));
        return $unpack['length'];
    }

    /**
     * @param string $data
     * @return int
     */
    static public function readSequence($data){
        $unpack = unpack('Nsequence', substr($data, 4, 4));
        return $unpack['sequence'];
    }

    /**
     * @param string $data
     * @return int
     */
    static public function readConnection($data){
        $unpack = unpack('Nconnection', substr($data, 8, 4));
        return $unpack['connection'];
    }

    /**
     * @param string $data
     * @return int
     */
    static public function readFactor($data){
        $unpack = unpack('Jfactor', substr($data, 12, 8));
        return $unpack['factor'];
    }

    /**
     * @param int $sequence
     * @param int $connection
     * @return string globally unique packet id
     */
    static public function getPacketId($sequence, $connection){
        return pack('NN', $sequence, $connection);
    }

    /**
     * @param string $packetId
     * @return array
     */
    static public function parsePacketId($packetId){
        return unpack('Nsequence/Nconnection', $packetId);
    }
}