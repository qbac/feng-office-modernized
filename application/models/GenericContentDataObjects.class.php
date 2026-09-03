<?php

/**
 * GenericContentDataObjects
 *
 * Konkretna, "bezosobowa" instancja ContentDataObjects - odpowiednik wywoływania
 * ContentDataObjects::listing()/getObjectTypeId() statycznie (bez $this) w PHP 5, gdzie
 * $this wewnątrz metody było cicho traktowane jako null. Od PHP 8 nie da się w ogóle wywołać
 * niestatycznej metody bez obiektu ($this musi istnieć zanim ciało metody się wykona), więc
 * potrzebna jest realna, "pusta" instancja z object_type_name = null (czyli generyczne
 * listowanie obiektów wszystkich typów, dokładnie jak wcześniej).
 */
class GenericContentDataObjects extends ContentDataObjects {

	function __construct() {
		parent::__construct('FengObject', 'objects', false);
	}

	static function getColumns() {
		return BaseObjects::getColumns();
	}

	function getColumnType($column_name) {
		return Objects::instance()->getColumnType($column_name);
	}

	function getPkColumns() {
		return 'id';
	}

	function getAutoIncrementColumn() {
		return 'id';
	}

} // GenericContentDataObjects

?>
