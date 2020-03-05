<?php

function db_connection() {
    $dbConf = include "config" . DIRECTORY_SEPARATOR . "db.php";

    $connection = mysqli_connect(
        $dbConf["host"],
        $dbConf["username"],
        $dbConf["password"],
        $dbConf["database"]
    );

    if ($connection == false || mysqli_errno($connection))
        die(mysqli_error($connection));

    return $connection;
}

function db_subdata_constructor($index, $keys, $values) {
    $str = " `{$keys[$index]}`=";

    $value = $values[$index];
    if (is_numeric($value))
        $str .= "$value";
    else if (is_string($value))
        $str .= "'$value'";
    else if (is_bool($value))
        $str .= $value ? "true" : "false";
    else if (is_null($value))
        $str .= "NULL";

    return $str;
}

function db_where_constructor(array $where) {

    $keys = array_keys($where);
    $values = array_values($where);

    $query = "";
    if (count($keys) > 0) {
        $query .= "WHERE" . db_subdata_constructor(0, $keys, $values);
        for ($i = 1; $i < count($keys); $i++)
            $query .= " AND WHERE" . db_subdata_constructor($i, $keys, $values);
    }

    return $query;
}

function db_data_constructor(array $data) {

    $str = "";
    $keys = array_keys($data);
    $values = array_values($data);

    foreach ($keys as $key => $value)
        $str .= db_subdata_constructor($key, $keys, $values);

    return $str;
}

function db_query($connection, $query) {
    $result = mysqli_query($connection, $query);

    if ($result === false)
        die(mysqli_error($connection));

    return $result;
}

function db_create($connection,$name,$collumnsInfo = []){
    $query = "CREATE TABLE $name (";

    foreach ($collumnsInfo as $col) {
        $query.= "{$col['name']} {$col['type']},"; 
    }

    $query.= ");";

    $res = db_query($connection, $query);
    mysqli_close($connection);

    return $res;
}

function db_drop_table($connection,$table)
{
    $query = "DROP TABLE ". $table;
  
    $res = db_query($connection, $query);
    mysqli_close($connection);

    return $res;
}

function db_alter_table($connection,$table,$columnname,$datatype)
{
    $query = "ALTER TABLE ". $table."
    MODIFY COLUMN ". $columnname ." ".$datatype;
  
    $res = db_query($connection, $query);
    mysqli_close($connection);

    return $res;
}

function db_select(string $table, array $where = [], array $cols = ["*"]) {
    $connection = db_connection();

    $where = db_where_constructor($where);
    $cols = implode(",", $cols);
    $query = "SELECT {$cols} FROM `{$table}` {$where}";

    $result = mysqli_query($connection, $query);

    if ($result === false)
        die(mysqli_error($connection));

    $rows = [];
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
        $rows[] = $row;

    mysqli_close($connection);
    return $rows;
}

function db_update(string $table, array $where, array $data) {
    $connection = db_connection();
    $where = db_where_constructor($where);
    $data = db_data_constructor($data);

    $query = "UPDATE {$table} SET {$data} {$where}";
    $res = db_query($connection, $query);
    mysqli_close($connection);

    return $res;
}

function db_insert($table, array $data) {

    $connection = db_connection();

    // Ключи в строку через запятую
    $keys = array_keys($data);
    $keys = implode(",", $keys);

    // Значкения в строку через запятую
    $values = array_values($data);
    $values = array_map(function ($item) {

        if (is_string($item))
            return "'$item'";

        if (is_null($item))
            return 'NULL';

        if (is_bool($item))
            return $item ? 'true' : 'false';

        return $item;

    }, $values);
    $values = implode(",", $values);

    $query = "INSERT INTO `{$table}` ({$keys}) VALUES ({$values})";
    $res = db_query($connection, $query);
    mysqli_close($connection);

    return $res;
}

function db_delete(string $table, array $where){
    $connecton = db_connection();

    $where = db_where_constructor($where);

    $query = "DELETE FROM $table $where";

    $res = db_query($connecton,  $query);

    mysqli_close();

}