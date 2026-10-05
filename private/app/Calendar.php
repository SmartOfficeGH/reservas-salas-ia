<?php
declare(strict_types=1);
final class Calendar
{
    public static function state(string $day,string $view='week',int $room=1):array
    {
        $zone=new DateTimeZone('Europe/Madrid');
        $date=DateTimeImmutable::createFromFormat('!Y-m-d',$day,$zone);
        if(!$date||$date->format('Y-m-d')!==$day)$date=new DateTimeImmutable('today',$zone);
        if(!in_array($view,['day','week','month'],true))$view='week';
        if(!in_array($room,[1,3],true))$room=1;
        $days=[];
        if($view==='week') {
            $start=$date->modify('-'.((int)$date->format('N')-1).' days');$end=$start->modify('+4 days');
            for($i=0;$i<5;$i++)$days[]=$start->modify("+$i days")->format('Y-m-d');
            $prev=$date->modify('-7 days');$next=$date->modify('+7 days');
        } elseif($view==='month') {
            $start=$date->modify('first day of this month');$end=$date->modify('last day of this month');
            for($d=$start;$d<=$end;$d=$d->modify('+1 day'))$days[]=$d->format('Y-m-d');
            $prev=self::shiftMonth($date,-1);$next=self::shiftMonth($date,1);
        } else {$start=$end=$date;$days=[$date->format('Y-m-d')];$prev=$date->modify('-1 day');$next=$date->modify('+1 day');}
        return ['day'=>$date->format('Y-m-d'),'view'=>$view,'room'=>$room,'start'=>$start->format('Y-m-d'),'end'=>$end->format('Y-m-d'),'days'=>$days,'prev'=>$prev->format('Y-m-d'),'next'=>$next->format('Y-m-d')];
    }
    private static function shiftMonth(DateTimeImmutable $date,int $direction):DateTimeImmutable
    {
        $month=$date->modify('first day of this month')->modify(($direction>0?'+':'').$direction.' month');
        return $month->modify('+'.(min((int)$date->format('j'),(int)$month->format('t'))-1).' days');
    }
    public static function url(array $state,array $changes=[]):string
    {
        $s=array_replace($state,$changes);
        $query=['day'=>$s['day'],'view'=>$s['view'],'room'=>$s['room']];
        return (!empty($s['public'])?'ocupacion.php?':'?page=agenda&').http_build_query($query).'#occupation-title';
    }
    public static function publicUrl(array $config,array $state,bool $includeDate):string
    {
        $query=['view'=>$state['view'],'room'=>$state['room']];
        if($includeDate)$query['day']=$state['day'];
        return rtrim($config['app_url'],'/').'/ocupacion.php?'.http_build_query($query);
    }
}
