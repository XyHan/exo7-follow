<?php

namespace App\Entity\Trait;

use App\Entity\HashableInterface;
use DateTimeInterface;
use Doctrine\Common\Collections\Collection;

trait Hashable
{
    public function hash(): string
    {
        $checkString = "";

        $arr = (array) $this;
        ksort($arr);

        foreach ($arr as $k => $v) {
            if ($v instanceof DatetimeInterface) {
                $v = $v->format(DATE_ATOM);
            }
            if (is_array($v) || $v instanceof Collection) {
                continue;
            }
            $checkString .= sprintf("[%s:%s]", $k, $v);
        }

        return sha1($checkString);
    }

    public function identicalTo(HashableInterface $obj): bool
    {
        return $this->hash() === $obj->hash();
    }
}
