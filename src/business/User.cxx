/*=========================================================================

  Program:  Alder (CLSA Medical Image Quality Assessment Tool)
  Module:   User.cxx
  Language: C++

  Author: Patrick Emond <emondpd AT mcmaster DOT ca>
  Author: Dean Inglis <inglisd AT mcmaster DOT ca>

=========================================================================*/
#include <User.h>

// Alder includes
#include <Modality.h>
#include <QueryModifier.h>
#include <Utilities.h>

// VTK includes
#include <vtkObjectFactory.h>

// C++ includes
#include <string>
#include <vector>

namespace Alder
{
  vtkStandardNewMacro(User);

  // -+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-
  void User::SetVariant(const std::string column, const vtkVariant aValue)
  {
    vtkVariant value = aValue;
    if ("Password" == column && value.IsValid())
    { // if we are setting the password override the parent so that we can hash
      std::string hashedPassword;
      Utilities::hashString(value.ToString(), hashedPassword);
      value = vtkVariant(hashedPassword);
    }
    else if ("Name" == column && 0 == value.ToString().size())
    { // don't allow empty user names
      std::stringstream error;
      error << "Tried to set column \""
            << this->GetName() << "." << column
            << "\" which doesn't exist";
      throw std::runtime_error(error.str());
    }

    this->Superclass::SetVariant(column, value);
  }

  // -+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-
  void User::ResetPassword()
  {
    this->Set("Password", User::GetDefaultPassword());
  }

  // -+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-
  bool User::IsPassword(const std::string& password)
  {
    // first hash the password argument
    std::string hashedPassword;
    Utilities::hashString(password, hashedPassword);
    return hashedPassword == this->Get("Password").ToString();
  }

  // -+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-
  void User::InitializeExamModifier(QueryModifier* modifier)
  {
    if (!modifier) return;
    modifier->Reset();

    std::string idStr;
    std::vector<vtkSmartPointer<Modality>> vecModality;
    this->GetList(&vecModality);

    for (auto it = vecModality.begin(); it != vecModality.end();)
    {
      idStr += (*it)->Get("Id").ToString();
      ++it;
      idStr += (it == vecModality.end()) ? "" : "','";
    }

    modifier->Join("ScanType", "ScanType.Id", "Exam.ScanTypeId");
    modifier->Join("Modality", "Modality.Id", "ScanType.ModalityId");
    modifier->Where("Modality.Id", "IN", vtkVariant(idStr));
  }
}  // namespace Alder
