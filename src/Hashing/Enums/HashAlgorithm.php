<?php

declare(strict_types=1);

namespace Maginium\Framework\Hashing\Enums;

use Maginium\Framework\Enum\Attributes\Description;
use Maginium\Framework\Enum\Attributes\Label;
use Maginium\Framework\Enum\Enum;

/**
 * Class HashAlgorithm.
 *
 * Enumeration of common hashing algorithms.
 *
 * @method static self MD5() MD5 hashing algorithm.
 * @method static self SHA1() SHA-1 hashing algorithm.
 * @method static self SHA256() SHA-256 hashing algorithm.
 * @method static self SHA512() SHA-512 hashing algorithm.
 * @method static self SHA3_256() SHA3-256 hashing algorithm.
 * @method static self SHA3_512() SHA3-512 hashing algorithm.
 * @method static self BLAKE2B() BLAKE2b hashing algorithm.
 * @method static self BLAKE2S() BLAKE2s hashing algorithm.
 * @method static self HMAC() HMAC (Hash-based Message Authentication Code).
 */
class HashAlgorithm extends Enum
{
    /**
     * MD5 hashing algorithm.
     */
    #[Label('MD5')]
    #[Description('MD5 (Message Digest Algorithm 5) is a widely used hashing algorithm producing a 128-bit hash value, commonly represented as a 32-character hexadecimal number.')]
    public const MD5 = 'md5';

    /**
     * SHA-1 hashing algorithm.
     */
    #[Label('SHA-1')]
    #[Description('SHA-1 (Secure Hash Algorithm 1) generates a 160-bit hash value, typically rendered as a 40-digit hexadecimal number. Although it was widely used, it is now considered weak due to vulnerabilities.')]
    public const SHA1 = 'sha1';

    /**
     * SHA-256 hashing algorithm.
     */
    #[Label('SHA-256')]
    #[Description('SHA-256 (Secure Hash Algorithm 256-bit) is part of the SHA-2 family, producing a 256-bit hash value, and is widely used in security applications and protocols.')]
    public const SHA256 = 'sha256';

    /**
     * SHA-512 hashing algorithm.
     */
    #[Label('SHA-512')]
    #[Description('SHA-512 (Secure Hash Algorithm 512-bit) is also part of the SHA-2 family, producing a 512-bit hash value. It is used where a higher level of security is required compared to SHA-256.')]
    public const SHA512 = 'sha512';

    /**
     * SHA3-256 hashing algorithm.
     */
    #[Label('SHA3-256')]
    #[Description('SHA3-256 is part of the SHA-3 family, designed as a successor to SHA-2 with a different underlying algorithm, producing a 256-bit hash value.')]
    public const SHA3_256 = 'sha3-256';

    /**
     * SHA3-512 hashing algorithm.
     */
    #[Label('SHA3-512')]
    #[Description('SHA3-512 is a member of the SHA-3 family, providing a 512-bit hash value with a different algorithm from SHA-2, offering an alternative approach to hashing.')]
    public const SHA3_512 = 'sha3-512';

    /**
     * BLAKE2b hashing algorithm.
     */
    #[Label('BLAKE2b')]
    #[Description('BLAKE2b is a high-speed cryptographic hash function optimized for 64-bit platforms, offering a similar level of security to the SHA-3 family but faster.')]
    public const BLAKE2B = 'blake2b';

    /**
     * BLAKE2s hashing algorithm.
     */
    #[Label('BLAKE2s')]
    #[Description('BLAKE2s is a variant of BLAKE2 designed for 32-bit platforms, providing high security and performance, making it suitable for various applications where space is limited.')]
    public const BLAKE2S = 'blake2s';

    /**
     * HMAC (Hash-based Message Authentication Code).
     */
    #[Label('HMAC')]
    #[Description('HMAC is a mechanism that combines a cryptographic hash function with a secret key to provide data integrity and authenticity.')]
    public const HMAC = 'hmac';
}
