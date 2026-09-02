<?php
header('Content-Type: text/html; charset=utf-8');
require_once("config/config.php");

mysql_connect(DB_HOST, DB_USER, DB_PASS)or die("Nie można nawi±zać poł±czenia z baz±");
mysql_select_db(DB_NAME)or die("Wyst±pił bł±d podczas wybierania bazy danych");
// SELECT * FROM fo_object_members JOIN fo_members ON fo_object_members.member_id = fo_members.id WHERE fo_members.object_type_id = 2 AND fo_object_members.object_id=18344
// SELECT * FROM fo_objects 
// LEFT JOIN fo_object_members ON fo_objects.id = fo_object_members.object_id
// WHERE fo_objects.object_type_id=5 AND fo_objects.created_on>="2014-01-01 00:00:00" AND fo_object_members.member_id = 15 AND fo_objects.trashed_by_id = 0
$sql = "SET CHARSET latin2";
mysql_query($sql);
$j=0;	
$wszystkie=0;
$s_nowe=0;
$s_sprzedane=0;
$s_zamowione=0;

	$zapytanie='SELECT id FROM fo_objects WHERE object_type_id=5 AND created_on>="2014-04-01 00:00:00" AND trashed_by_id = 0';
	$wykonaj = mysql_query($zapytanie);
	while ($wiersz = mysql_fetch_array($wykonaj)){
	//echo $wiersz[1]."<br>";
	$i=0;
	$nowe=0;
	$sprzedane=0;
	$zamowione=0;
	$zapytanie2='SELECT * FROM fo_object_members WHERE  object_id='.$wiersz['id'];
	$wykonaj2 = mysql_query($zapytanie2);
	while ($wiersz2 = mysql_fetch_array($wykonaj2)){
	if ($wiersz2['member_id']==14) {$i=1;}
	if ($wiersz2['member_id']==15) {$nowe=1;}
	if ($wiersz2['member_id']==20) {$zamowione=1;}
	if ($wiersz2['member_id']==21) {$sprzedane=1;}
	
	if ($i==1 && $nowe==1) {$s_nowe++; $i=0; $nowe=0;}
	if ($i==1 && $zamowione==1) {$s_zamowione++; $i=0; $zamowione=0;}
	if ($i==1 && $sprzedane==1) {$s_sprzedane++; $i=0; $sprzedane=0;}
	}
	$wszystkie++;
}
echo "Nowe: ".$s_nowe;
echo "<br>Zamówione: ".$s_zamowione;
echo "<br>Sprzedane: ".$s_sprzedane;
echo "<br>Wszystkie ".$wszystkie;

echo "<h1>Konwersja: ". round((($s_zamowione + $s_sprzedane)*100)/$wszystkie, 2)." %</h1>";
?>
