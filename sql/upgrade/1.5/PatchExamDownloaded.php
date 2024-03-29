#!/usr/bin/php
<?php
/**
 *
 * @author Dean Inglis <inglisd@mcmaster.ca>
 */

ini_set( 'display_errors', '1' );
error_reporting( E_ALL | E_STRICT );
ini_set( 'date.timezone', 'US/Eastern' );

// utility functions
function out( $msg ) { printf( '%s: %s'."\n", date( 'Y-m-d H:i:s' ), $msg ); }
function error( $msg ) { out( sprintf( 'ERROR! %s', $msg ) ); die(); }

  function my_execute( $connection, $sql )
  {
    $result = $connection->Execute( $sql );
    if( false === $result )
    {
      out( $connection->ErrorMsg() );
      error( $sql );
    }
  }

  function my_get_one( $connection, $sql )
  {
    $result = $connection->GetOne( $sql );
    if( false === $result )
    {
      out( $connection->ErrorMsg() );
      error( $sql );
    }
    return $result;
  }

  function my_get_all( $connection, $sql )
  {
    $result = $connection->GetAll( $sql );
    if( false === $result )
    {
      out( $connection->ErrorMsg() );
      error( $sql );
    }
    return $result;
  }

  function xml2assoc( &$xml )
  {
    $assoc = NULL;
    while( $xml->read() )
    {
      if( XMLReader::END_ELEMENT == $xml->nodeType )
        break;
      if( XMLReader::ELEMENT == $xml->nodeType && !$xml->isEmptyElement )
      {
        $key = $xml->name;
        if( $xml->hasAttributes )
        {
          while( $xml->moveToNextAttribute() )
            $assoc[$key][$xml->name] = $xml->value;
        }
        $assoc[$key] = xml2assoc( $xml );
      }
      else if( $xml->isEmptyElement )
      {
        break;
      }
      else if( XMLReader::TEXT == $xml->nodeType )
      {
        $assoc = $xml->value;
      }
    }

    return $assoc;
  }

  if( $argc < 2  )
  {
    error( 'usage: PatchExamDownloaded.php path_to/config.xml <debug> \n' );
    exit();
  }

  $filename = $argv[1];
  if( !file_exists( $filename ) || !is_readable( $filename ) )
    error( 'cannot open file ' . $filename );

  $xml = new XMLReader();
  $xml->open( $filename );

  $assoc = xml2assoc($xml);

  $dbkeys = array( 'Host','Port','Username','Password','Name' );
  $dbassoc = '';
  try
  {
    $dbassoc = $assoc['Configuration']['Database'];
    foreach( $dbkeys as $key )
      if( !array_key_exists( $key, $dbassoc) )
      {
        error( 'missing database configuration element; ', $key );
      }
  }
  catch( Exception $e )
  {
    error( $e->getMessage() );
  }

  // open connection to the database
  require_once '/usr/local/lib/adodb5/adodb.inc.php';
  $db = ADONewConnection( 'mysql' );
  $db->SetFetchMode( ADODB_FETCH_ASSOC );

  $result = $db->Connect( $dbassoc['Host'], $dbassoc['Username'], $dbassoc['Password'],  $dbassoc['Name'] );
  if( false === $result )
  {
    error( 'Unable to connect to database, quiting' );
  }

  $debug = 1;
  if( 3 == $argc )
    $debug = $argv[2];
  $remove=0;
  $reverse=0;
  $reverseToDownloaded=0;
  $numExamProcessed=0;
  $numImageProcessed=0;

  // correct Exam records with Downloaded = 1 but no Image record
  $sql = 'SELECT Exam.Id AS examId, '.
         'Exam.Side AS side, '.
         'Exam.InterviewId AS interviewId, '.
         'Exam.ScanTypeId AS typeId '.
         'FROM Exam '.
         'LEFT JOIN Image ON Image.ExamId=Exam.Id '.
         'WHERE Image.Id IS NULL '.
         'AND Exam.Downloaded=1';
  $data = my_get_all( $db, $sql );
  if( 0 < count( $data ) )
  {
    out( 'repairing ' . count( $data ) .' Exam records having Downloaded with no Image record' );
    foreach( $data as $elem )
    {
      $sql = 'UPDATE Exam '.
             'SET Downloaded=0 '.
             'WHERE Id=' . $elem['examId'] . ' '.
             'AND Side="'. $elem['side'] . '" '.
             'AND InterviewId=' . $elem['interviewId'] . ' '.
             'AND ScanTypeId=' . $elem['typeId'];
      if( 0 == $debug )
        my_execute( $db, $sql );
      $reverse++;
      $numExamProcessed++;
    }
  }

  // get all Image records
  $sql = 'SELECT Image.ExamId AS examId, Image.Id AS imageId, '.
         'CONCAT( Interview.Id,"/",Exam.Id,"/",Image.Id) AS path, '.
         'Exam.Downloaded AS downloaded, '.
         'Exam.InterviewId AS interviewId, '.
         'Exam.Side AS side, '.
         'Exam.ScanTypeId AS typeId, '.
         'ScanType.FileSuffix AS suffix, '.
         'IFNULL(Child.Id,"none") AS childId '.
         'FROM Image '.
         'JOIN Exam ON Exam.Id=Image.ExamId '.
         'JOIN Interview ON Interview.Id=Exam.InterviewId '.
         'JOIN ScanType ON ScanType.Id=Exam.ScanTypeId '.
         'LEFT JOIN Image AS Child ON Child.ParentImageId=Image.Id';
  $data = my_get_all( $db, $sql );
  $imagepath = '';
  try
  {
    $imagePath = $assoc['Configuration']['Path']['ImageData'];
  }
  catch( Exception $e )
  {
    error( $e->getMessage() );
  }

  foreach( $data as $elem )
  {
    $path = $imagePath . '/' . $elem['path'];
    $suffix = $elem['suffix'];
    if( 4 < strlen( $suffix ) )
      $suffix = substr( $suffix, 0, 4 );
    $downloaded = $elem['downloaded'];
    $expectedFile = $path . $suffix;
    $reload = 0;
    $files = glob( $path . '.*' );
    foreach( $files as $file )
    {
      // remove or unzip the file
      if( 0 == filesize( $file ) )
      {
        if( 0 == $debug )
          system( 'rm '. escapeshellarg( $file ) );
        $reload = 1;
      }
      else
      {
        if( false !== stristr( '.gz', $file ) )
        {
          if( 0 == $debug )
            system( 'gunzip ' . escapeshellarg( $file ) );
          $reload = 1;
        }
      }
    }
    if( $reload )
    {
      $files = glob( $path . '.*' );
    }
    $fileExists = 0 != count( $files );

    $doRemove = 0;
    $doReverse = 0;
    if( $fileExists )
    {
      if( !file_exists( $expectedFile ) )
      {
        out( 'the expected file .' . $expectedFile . ' is not available among ' . implode( ',', $files ) );
        $doRemove = 1;
        if( 1 == $downloaded )
          $doReverse = 1;
      }
      else
      {
        if( 0 == $downloaded )
          $doReverse = 1;
      }
    }
    else
    {
      $doRemove = 1;
      $doReverse = 1;
    }
    if( $doRemove )
    {
      if( 'none' != $elem['childId'] )
      {
        // remove both child and parent
        out( 'removing Image record '. $elem['imageId'] . ' with child '. $elem['childId'] );
        $sql = 'SELECT Image.Id AS imageId, '.
               'Exam.Id AS examId, '.
               'Exam.Side AS side, '.
               'Exam.InterviewId AS interviewId, '.
               'Exam.ScanTypeId AS typeId, '.
               'IFNULL(ParentImageId,"none") AS parentId '.
               'FROM Image '.
               'JOIN Exam ON Exam.Id=Image.ExamId '.
               'WHERE Image.Id IN ('. implode( ',', array($elem['imageId'],$elem['childId']) ) . ') '.
               'ORDER BY Image.Id DESC';
        $innerData = my_get_all( $db, $sql );
        foreach( $innerData as $innerElem )
        {
          $sql = 'DELETE Image.* FROM Image WHERE Id='. $innerElem['imageId'];
          if( 0 == $debug )
            my_execute( $db, $sql );
          $remove++;
          $numImageProcessed++;
 
          $sql = 'Update Exam SET '.
                 'Downloaded=0 '.
                 'WHERE Id='. $innerElem['examId'] . ' ' .
                 'AND Side="' . $innerElem['side'] . '" ' .
                 'AND InterviewId=' . $innerElem['interviewId'] . ' '.
                 'AND ScanTypeId=' . $innerElem['typeId'];
          if( 0 == $debug )
            my_execute( $db, $sql );
          $reverse++;
          $numExamProcessed++;
        }
      }
      else
      {
        $sql = 'DELETE Image.* FROM Image WHERE Id='. $elem['imageId'];
        if( 0 == $debug )
          my_execute( $db, $sql );
        $remove++;
        $numImageProcessed++;
      }
    }
    if( $doReverse )
    {
      $value = ( 0 == $downloaded ) ? 1 : 0;
      if( 1 == $value )
        $reverseToDownloaded++;

      $sql = 'Update Exam SET '.
             'Downloaded=' . $value . ' '.
             'WHERE Id='. $elem['examId'] . ' ' .
             'AND Side="' . $elem['side'] . '" '.
             'AND InterviewId=' . $elem['interviewId'] . ' '.
             'AND ScanTypeId=' . $elem['typeId'];
      if( 0 == $debug )
        my_execute( $db, $sql );
      $reverse++;
      $numExamProcessed++;
    }
  }

  out( 'number of Image records processed: ' . $numImageProcessed );
  out( 'number of Exam records processed: ' . $numExamProcessed );
  out( 'number of Image record removals: '. $remove );
  out( 'number of Exam Downloaded column reversals: '. $reverse );
  out( 'number reversed to Downloaded: '. $reverseToDownloaded );
  out( 'number reversed to NOT Downloaded: '. ($reverse - $reverseToDownloaded) );
?>
