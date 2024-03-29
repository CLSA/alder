/*=========================================================================

  Program:  Alder (CLSA Medical Image Quality Assessment Tool)
  Module:   OpalService.h
  Language: C++

  Author: Patrick Emond <emondpd AT mcmaster DOT ca>
  Author: Dean Inglis <inglisd AT mcmaster DOT ca>

=========================================================================*/

/**
 * @class OpalService
 * @namespace Alder
 *
 * @author Patrick Emond <emondpd AT mcmaster DOT ca>
 * @author Dean Inglis <inglisd AT mcmaster DOT ca>
 *
 * @brief Class for interacting with Opal
 *
 * This class provides a programming interface to Opal's RESTful interface by using the
 * curl library.  A description of Opal can be found
 * <a href="http://www.obiba.org/?q=node/63">here</a>.
 */

#ifndef __OpalService_h
#define __OpalService_h

// Alder includes
#include <ModelObject.h>

// VTK includes
#include <vtkMySQLQuery.h>
#include <vtkSmartPointer.h>

// curl includes
#include </usr/include/curl/curl.h>
#include </usr/include/curl/stdcheaders.h>

// json includes
#include <json/reader.h>

// C++ includes
#include <iostream>
#include <map>
#include <string>
#include <vector>

/**
 * @addtogroup Alder
 * @{
 */

namespace Alder
{
  class User;
  class OpalService : public ModelObject
  {
    public:
      static OpalService* New();
      vtkTypeMacro(OpalService, ModelObject);

      /**
       * Defines connection parameters to use when communicating with
       * the Opal server.
       * @param username Opal user name
       * @param password Opal user password
       * @param host     Opal url
       * @param port     Opal connection port
       * @param timeout  Opal connection timeout
       * @param vervose  Opal connection verbosity
       */
      void Setup(
        const std::string& username,
        const std::string& password,
        const std::string& host,
        const int& port = 8843,
        const int& timeout = 10,
        const int& verbose = 0);

      /**
       *  Set/Get the Opal connection port.
       */
      vtkGetMacro(Port, int);
      vtkSetMacro(Port, int);

      /**
       * Set/Get the Opal connection timeout.
       */
      vtkGetMacro(Timeout, int);
      vtkSetMacro(Timeout, int);

      /**
       * Set/Get the state of a sustained Curl connection.
       */
      void SetSustainConnection(int sustain);
      vtkGetMacro(SustainConnection, int);
      vtkBooleanMacro(SustainConnection, int);

      /**
       * Returns a list of all identifiers in a particular data source and table.
       * @param dataSource name of an Opal data source
       * @param table      name of a table within the data source
       * @throws           std::runtime_error
       * @return           vector of identifiers
       */
      std::vector<std::string> GetIdentifiers(const std::string& dataSource,
        const std::string& table) const;

      /**
       * Returns all variables for all identifiers limited by the offset
       * and limit parameters.
       * @param dataSource name of an Opal data source
       * @param table      name of a table within the data source
       * @param offset     the offset to begin the list at
       * @param limit      the number of key/value pairs to return
       * @throws           std::runtime_error
       * @return           map of identifier keys to mapped data set values
       */
      std::map<std::string, std::map<std::string, std::string>> GetRows(
        const std::string& dataSource, const std::string& table,
        const int& offset = 0, const int& limit = 100) const;

      /**
       * Returns all variables for a given identifier.
       * @param dataSource name of an Opal data source
       * @param table      name of a table within the data source
       * @param identifier an Interview UId, or Wave Name
       * @throws           std::runtime_error
       * @return           map of variable names to values
       */
      std::map<std::string, std::string> GetRow(
        const std::string& dataSource, const std::string& table,
        const std::string& identifier) const;

      /**
       * Returns all values for a particular variable limited by the offset
       * and limit parameters.
       * @param dataSource name of an Opal data source
       * @param table      name of a table within the data source
       * @param variable   name of a table variable
       * @param offset     the offset to begin the list at
       * @param limit      the number of key/value pairs to return
       * @throws           std::runtime_error
       * @return           map of identifiers to variable values
       */
      std::map<std::string, std::string> GetColumn(
        const std::string& dataSource, const std::string& table,
        const std::string& variable,
        const int& offset = 0, const int& limit = 100);

      /**
       * Returns a particular variable for a given identifier.
       * @param dataSource name of an Opal data source
       * @param table      name of a table within the data source
       * @param identifier an Interview UId, or Wave Name
       * @param variable   name of a table variable
       * @throws           std::runtime_error
       * @return           the value of a variable
       */
      std::string GetValue(
        const std::string& dataSource, const std::string& table,
        const std::string& identifier, const std::string& variable) const;

      /**
       * Returns an array of variables for a given identifier.
       * @param dataSource name of an Opal data source
       * @param table      name of a table within the data source
       * @param identifier an Interview UId, or Wave Name
       * @param variable   name of a table variable
       * @throws           std::runtime_error
       * @return           the values of a variable
       */
      std::vector<std::string> GetValues(
        const std::string& dataSource, const std::string& table,
        const std::string& identifier, const std::string& variable) const;

      /**
       * Returns an array of variable names within a table.
       * @param dataSource name of an Opal data source
       * @param table      name of a table within the data source
       * @throws           std::runtime_error
       * @return           the variables of a table
       */
      std::vector<std::string> GetVariables(
        const std::string& dataSource, const std::string& table) const;

      /**
       * Returns an array of table names within a data source.
       * @param dataSource name of an Opal data source
       * @throws           std::runtime_error
       * @return           the tables of a data source
       */
      std::vector<std::string> GetTables(
        const std::string& dataSource) const;

      /**
       * Save a binary blob Opal variable (i.e., an image) as a file.
       * @param fileName name of a file to save data to
       * @param dataSource name of an Opal data source
       * @param table      name of a table within the data source
       * @param identifier an Interview UId, or Wave Name
       * @param variable   name of a table variable
       * @param position   if the data is repeatable, specify its cardinality
       * @throws           std::runtime_error
       */
      void SaveFile(
        const std::string& fileName,
        const std::string& dataSource,
        const std::string& table,
        const std::string& identifier,
        const std::string& variable,
        const int& position = -1) const;

    protected:
      OpalService();
      ~OpalService();

      /**
       * Returns the response provided by Opal for a given service path, or if fileName is not
       * empty then writes the response to the given filename (returning an empty json value).
       * @param servicePath a path to the requested data in Opal
       * @param fileName    name of a file to save the data to
       * @throws            std::runtime_error
       * @return            jason formatted data
       */
      virtual Json::Value Read(
        const std::string& servicePath, const std::string& fileName = "") const;

      std::map<std::string,
        std::map<std::string,
          std::map<std::string, std::string>>> Columns;
      std::string Username;
      std::string Password;
      std::string Host;
      int Port;
      int Timeout;
      int Verbose;
      int SustainConnection;
      CURL* CurlConnection;
      struct curl_slist* CurlHeaders;
      std::string CurlCredentials;

    private:
      OpalService(const OpalService&);  /** Not implemented. */
      void operator=(const OpalService&);  /** Not implemented. */
  };
}  // namespace Alder

/** @} end of doxygen group */

#endif
