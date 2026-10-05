<?php
declare(strict_types=1);

final class Occupancy
{
    public static function minute(string $time): float
    {
        $parts=array_map('intval',explode(':',$time));
        return $parts[0]*60+$parts[1]+($parts[2]??0)/60;
    }
    public static function clock(float $minute): string { return sprintf('%02d:%02d',intdiv((int)$minute,60),(int)$minute%60); }

    public static function slots(string $day, array $bookings, ?DateTimeImmutable $now=null): array
    {
        $zone=new DateTimeZone('Europe/Madrid');
        $now??=new DateTimeImmutable('now',$zone);
        $allowed=(int)(new DateTimeImmutable($day,$zone))->format('N')<=5;
        usort($bookings,fn($a,$b)=>strcmp($a['starts_at'],$b['starts_at']));
        $slots=[];
        for($start=420;$start<960;$start+=30) {
            $gaps=[[$start,$start+30]];
            foreach($bookings as $r) {
                $rs=self::minute($r['starts_at']);$re=self::minute($r['ends_at']);$remaining=[];
                foreach($gaps as [$a,$b]) {
                    if($re<=$a||$rs>=$b){$remaining[]=[$a,$b];continue;}
                    if($rs>$a)$remaining[]=[$a,min($b,$rs)];
                    if($re<$b)$remaining[]=[max($a,$re),$b];
                }
                $gaps=$remaining;
            }
            foreach($gaps as [$a,$b]) {
                // Las reservas creadas por la aplicación usan minutos completos.
                // Si hay datos históricos con segundos, avanzar al siguiente minuto libre.
                $a=ceil($a);if($a>=$b)continue;
                $until=960;
                foreach($bookings as $r) { $rs=self::minute($r['starts_at']);if($rs>=$a)$until=min($until,$rs); }
                $at=new DateTimeImmutable($day.' '.self::clock($a),$zone);
                $slots[]=['start'=>$a,'end'=>$b,'start_at'=>$at->format(DATE_ATOM),'allowed'=>$allowed&&$at>=$now,
                    'suggested_end'=>$a+30<=$until?self::clock($a+30):''];
            }
        }
        return $slots;
    }
}
