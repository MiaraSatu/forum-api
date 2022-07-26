<?php 

namespace App\Service;

use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;

class SerializerService {
	public static function getSerializer() {
		$encoders = [new JsonEncoder()];
		$normalizers = [new ObjectNormalizer()];
		return new Serializer($normalizers, $encoders);
	}
}