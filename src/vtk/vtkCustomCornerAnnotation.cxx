/*=========================================================================

  Module:    vtkCustomCornerAnnotation.cxx
  Program:   Alder (CLSA Medical Image Quality Assessment Tool)
  Language:  C++
  Author:    Patrick Emond <emondpd AT mcmaster DOT ca>
  Author:    Dean Inglis <inglisd AT mcmaster DOT ca>

=========================================================================*/
#include <vtkCustomCornerAnnotation.h>

// VTK includes
#include <vtkAlgorithmOutput.h>
#include <vtkImageData.h>
#include <vtkImageSlice.h>
#include <vtkImageSliceMapper.h>
#include <vtkImageWindowLevel.h>
#include <vtkObjectFactory.h>
#include <vtkPropCollection.h>
#include <vtkTextMapper.h>
#include <vtkTextProperty.h>
#include <vtkViewport.h>
#include <vtkWindow.h>

vtkStandardNewMacro(vtkCustomCornerAnnotation);

vtkSetObjectImplementationMacro(vtkCustomCornerAnnotation, ImageSlice, vtkImageSlice);
vtkSetObjectImplementationMacro(vtkCustomCornerAnnotation, WindowLevel, vtkImageWindowLevel);
vtkCxxSetObjectMacro(vtkCustomCornerAnnotation, TextProperty, vtkTextProperty);

// -+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-
vtkCustomCornerAnnotation::vtkCustomCornerAnnotation()
{
  this->PositionCoordinate->SetCoordinateSystemToNormalizedViewport();
  this->PositionCoordinate->SetValue(0.2, 0.85);

  this->LastSize[0] = 0;
  this->LastSize[1] = 0;

  this->MaximumLineHeight = 1.0;
  this->MinimumFontSize = 6;
  this->MaximumFontSize = 200;
  this->LinearFontScaleFactor    = 5.0;
  this->NonlinearFontScaleFactor = 0.35;
  this->FontSize = 15;
  this->TextProperty = NULL;

  vtkSmartPointer<vtkTextProperty> tprop = vtkSmartPointer<vtkTextProperty>::New();
  tprop->ShadowOff();
  double color[3] = {1., 1., 1.};
  tprop->SetColor(color);
  this->SetTextProperty(tprop);

  for (int i = 0; i < 4; ++i)
    {
    this->CornerText[i].clear();
    this->TextMapper[i] = vtkSmartPointer<vtkTextMapper>::New();
    this->TextActor[i] = vtkSmartPointer<vtkActor2D>::New();
    this->TextActor[i]->SetMapper(this->TextMapper[i]);
    }

  this->ImageSlice = 0;
  this->LastImageSlice = 0;
  this->WindowLevel = 0;

  this->LevelShift = 0;
  this->LevelScale = 1;

  this->ShowSliceAndImage = 1;
}

// -+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-
vtkCustomCornerAnnotation::~vtkCustomCornerAnnotation()
{
  this->SetTextProperty(0);
  this->SetWindowLevel(0);
  this->SetImageSlice(0);
}

/**
 * Release any graphics resources that are being consumed by this actor.
 * The parameter window could be used to determine which graphic
 * resources to release.
 */
// -+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-
void vtkCustomCornerAnnotation::ReleaseGraphicsResources(vtkWindow* win)
{
  this->Superclass::ReleaseGraphicsResources(win);
  for (int i = 0; i < 4; ++i)
    {
    this->TextActor[i]->ReleaseGraphicsResources(win);
    }
}

#define text_swap(a, b, c) { a = b; b = c; c = a; }

// -+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-
void vtkCustomCornerAnnotation::TextReplace(
  vtkImageSlice* ia, vtkImageWindowLevel* wl)
{
  int i;
  char* text, *text2;
  int slice = 0, slice_max = 0;
  char* rpos, *tmp;
  double window = 0, level = 0;
  long int windowi = 0, leveli = 0;
  vtkImageData* wl_input = 0, *ia_input = 0;
  int input_type_is_float = 0;

  if (wl)
    {
    window = wl->GetWindow();
    window *= this->LevelScale;
    level = wl->GetLevel();
    level = level*this->LevelScale + this->LevelShift;
    windowi = static_cast<long int>(window);
    leveli = static_cast<long int>(level);
    wl_input = vtkImageData::SafeDownCast(wl->GetInput());
    if (wl_input)
      {
      input_type_is_float = (wl_input->GetScalarType() == VTK_FLOAT ||
                             wl_input->GetScalarType() == VTK_DOUBLE);
      }
    }
  if (ia)
    {
    vtkImageSliceMapper* mapper = vtkImageSliceMapper::SafeDownCast(ia->GetMapper());
    slice = mapper->GetSliceNumber() - mapper->GetSliceNumberMinValue() + 1;
    slice_max = mapper->GetSliceNumberMaxValue() - mapper->GetSliceNumberMinValue() + 1;
    ia_input = mapper->GetInput();
    if (!wl_input && ia_input)
      {
      input_type_is_float = (ia_input->GetScalarType() == VTK_FLOAT ||
                             ia_input->GetScalarType() == VTK_DOUBLE);
      }
    }


  // search for tokens, replace and then assign to TextMappers
  for (i = 0; i < 4; ++i)
    {
    if (!this->CornerText[i].empty())
      {
      text = new char[this->CornerText[i].size() + 1000];
      text2 = new char[this->CornerText[i].size() + 1000];
      strcpy(text, this->CornerText[i].c_str());

      // now do the replacements

      // pointer to first occurence of <image> in text
      rpos = strstr(text, "<image>");
      while (rpos)
        {
        *rpos = '\0';
        if (ia && this->ShowSliceAndImage)
          {
          sprintf(text2, "%sImage: %i%s", text, slice, rpos + 7);
          }
        else
          {
          sprintf(text2, "%s%s", text, rpos + 7);
          }
        text_swap(tmp, text, text2);
        rpos = strstr(text, "<image>");
        }

      rpos = strstr(text, "<image_and_max>");
      while (rpos)
        {
        *rpos = '\0';
        if (ia && this->ShowSliceAndImage)
          {
          sprintf(text2, "%sImage: %i / %i%s", text, slice, slice_max, rpos + 15);
          }
        else
          {
          sprintf(text2, "%s%s", text, rpos + 15);
          }
        text_swap(tmp, text, text2);
        rpos = strstr(text, "<image_and_max>");
        }

      rpos = strstr(text, "<slice>");
      while (rpos)
        {
        *rpos = '\0';
        if (ia && this->ShowSliceAndImage)
          {
          sprintf(text2, "%sSlice: %i%s", text, slice, rpos + 7);
          }
        else
          {
          sprintf(text2, "%s%s", text, rpos + 7);
          }
        text_swap(tmp, text, text2);
        rpos = strstr(text, "<slice>");
        }

      rpos = strstr(text, "<slice_and_max>");
      while (rpos)
        {
        *rpos = '\0';
        if (ia && this->ShowSliceAndImage)
          {
          sprintf(text2, "%sSlice: %i / %i%s", text, slice, slice_max, rpos + 15);
          }
        else
          {
          sprintf(text2, "%s%s", text, rpos + 15);
          }
        text_swap(tmp, text, text2);
        rpos = strstr(text, "<slice_and_max>");
        }

      rpos = strstr(text, "<slice_pos>");
      while (rpos)
        {
        *rpos = '\0';
        if (ia && this->ShowSliceAndImage)
          {
          int w =
            vtkImageSliceMapper::SafeDownCast(
              ia->GetMapper())->GetOrientation();
          double pos;
          switch (w)
          {
            case 0: pos = ia->GetMinXBound(); break;
            case 1: pos = ia->GetMinYBound(); break;
            case 2: pos = ia->GetMinZBound(); break;
          }
          sprintf(text2, "%s%g%s", text, pos, rpos + 11);
          }
        else
          {
          sprintf(text2, "%s%s", text, rpos + 11);
          }
        text_swap(tmp, text, text2);
        rpos = strstr(text, "<slice_pos>");
        }

      rpos = strstr(text, "<window>");
      while (rpos)
        {
        *rpos = '\0';
        if (wl)
          {
          if (input_type_is_float)
            {
            sprintf(text2, "%sWindow: %g%s", text, window, rpos + 8);
            }
          else
            {
            sprintf(text2, "%sWindow: %li%s", text, windowi, rpos + 8);
            }
          }
        else
          {
          sprintf(text2, "%s%s", text, rpos + 8);
          }
        text_swap(tmp, text, text2);
        rpos = strstr(text, "<window>");
        }

      rpos = strstr(text, "<level>");
      while (rpos)
        {
        *rpos = '\0';
        if (wl)
          {
          if (input_type_is_float)
            {
            sprintf(text2, "%sLevel: %g%s", text, level, rpos + 7);
            }
          else
            {
            sprintf(text2, "%sLevel: %li%s", text, leveli, rpos + 7);
            }
          }
        else
          {
          sprintf(text2, "%s%s", text, rpos + 7);
          }
        text_swap(tmp, text, text2);
        rpos = strstr(text, "<level>");
        }

      rpos = strstr(text, "<window_level>");
      while (rpos)
        {
        *rpos = '\0';
        if (wl)
          {
          if (input_type_is_float)
            {
            sprintf(text2, "%sWW/WL: %g / %g%s", text, window, level, rpos + 14);
            }
          else
            {
            sprintf(text2, "%sWW/WL: %li / %li%s", text, windowi, leveli, rpos + 14);
            }
          }
        else
          {
          sprintf(text2, "%s%s", text, rpos + 14);
          }
        text_swap(tmp, text, text2);
        rpos = strstr(text, "<window_level>");
        }

      this->TextMapper[i]->SetInput(text);
      delete [] text;
      delete [] text2;
      }
    else
      {
      this->TextMapper[i]->SetInput("");
      }
    }
}

// -+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-
int vtkCustomCornerAnnotation::RenderOverlay(vtkViewport* viewport)
{
  // Everything is built, just have to render
  // only render if font is at least minimum font
  if (this->FontSize >= this->MinimumFontSize)
    {
    for (int i = 0; i < 4; ++i)
      {
      this->TextActor[i]->RenderOverlay(viewport);
      }
    }
  return 1;
}

namespace {
// Ported from old vtkTextMapper implementation
int GetNumberOfLines(const char* str)
{
  if (str == NULL || *str == '\0')
    {
    return 0;
    }

  int result = 1;
  while (str != NULL)
    {
    if ((str = strstr(str, "\n")) != NULL)
      {
      result++;
      str++;  // skip '\n'
      }
    }
  return result;
}
}  // namespace

// -+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-
int vtkCustomCornerAnnotation::RenderOpaqueGeometry(vtkViewport* viewport)
{
  int fontSize;
  int i;

  // Check to see whether we have to rebuild everything
  // If the viewport has changed we may - or may not need
  // to rebuild, it depends on if the projected coords chage
  int viewport_size_has_changed = 0;
  if (viewport->GetMTime() > this->BuildTime ||
      (viewport->GetVTKWindow() &&
       viewport->GetVTKWindow()->GetMTime() > this->BuildTime))
    {
    int* vSize = viewport->GetSize();
    if (this->LastSize[0] != vSize[0] || this->LastSize[1] != vSize[1])
      {
      viewport_size_has_changed = 1;
      }
    }

  // Is there an image actor ?
  vtkImageWindowLevel* wl = this->WindowLevel;
  vtkImageSlice* ia = 0;
  if (this->ImageSlice)
    {
    ia = this->ImageSlice;
    }
  else
    {
    vtkPropCollection* pc = viewport->GetViewProps();
    int numProps = pc->GetNumberOfItems();
    for (i = 0; i < numProps; ++i)
      {
      ia = vtkImageSlice::SafeDownCast(pc->GetItemAsObject(i));
      if (ia)
        {
        if (ia->GetMapper()->GetInput() && !wl)
          {
          wl = vtkImageWindowLevel::SafeDownCast(
            ia->GetMapper()->GetInputAlgorithm());
          }
        break;
        }
      }
    }

  int tprop_has_changed = (this->TextProperty &&
                           this->TextProperty->GetMTime() > this->BuildTime);

  // Check to see whether we have to rebuild everything
  if (viewport_size_has_changed ||
      tprop_has_changed ||
      (this->GetMTime() > this->BuildTime) ||
      (ia && (ia != this->LastImageSlice ||
              ia->GetMTime() > this->BuildTime)) ||
      (wl && wl->GetMTime() > this->BuildTime))
    {
    int* vSize = viewport->GetSize();

    vtkDebugMacro(<< "Rebuilding text");

    // Replace text
    this->TextReplace(ia, wl);

    // Get the viewport size in display coordinates
    this->LastSize[0] = vSize[0];
    this->LastSize[1] = vSize[1];

    // Only adjust size then the text changes due to non w/l slice reasons
    if (viewport_size_has_changed ||
        tprop_has_changed ||
        this->GetMTime() > this->BuildTime)
      {
      // Rebuid text props.
      // Perform shallow copy here since each individual corner has a
      // different aligment/size but they share the other this->TextProperty
      // attributes.
      fontSize = this->TextMapper[0]->GetTextProperty()->GetFontSize();

      if (tprop_has_changed)
        {
        vtkTextProperty* tprop = this->TextMapper[0]->GetTextProperty();
        tprop->ShallowCopy(this->TextProperty);
        tprop->SetFontSize(fontSize);

        tprop = this->TextMapper[1]->GetTextProperty();
        tprop->ShallowCopy(this->TextProperty);
        tprop->SetFontSize(fontSize);

        tprop = this->TextMapper[2]->GetTextProperty();
        tprop->ShallowCopy(this->TextProperty);
        tprop->SetFontSize(fontSize);

        tprop = this->TextMapper[3]->GetTextProperty();
        tprop->ShallowCopy(this->TextProperty);
        tprop->SetFontSize(fontSize);

        this->SetTextActorsJustification();
        }

      // Update all the composing objects to find the best size for the font
      // use the last size as a first guess

      /*
          +--------+
          |2      3|
          |        |
          |        |
          |0      1|
          +--------+
      */

      int tempi[8];
      int allZeros = 1;
      for (i = 0; i < 4; ++i)
        {
        this->TextMapper[i]->GetSize(viewport, tempi + i * 2);
        if (tempi[2*i] > 0 || tempi[2*i+1] > 0)
          {
          allZeros = 0;
          }
        }

      if (allZeros)
        {
        return 0;
        }

      int height_02 = tempi[1] + tempi[5];
      int height_13 = tempi[3] + tempi[7];

      int width_01 = tempi[0] + tempi[2];
      int width_23 = tempi[4] + tempi[6];

      int max_width = (width_01 > width_23) ? width_01 : width_23;

      int num_lines_02 = GetNumberOfLines(this->TextMapper[0]->GetInput()) +
        GetNumberOfLines(this->TextMapper[2]->GetInput());

      int num_lines_13 = GetNumberOfLines(this->TextMapper[1]->GetInput()) +
        GetNumberOfLines(this->TextMapper[3]->GetInput());

      int line_max_02 = static_cast<int>(vSize[1] * this->MaximumLineHeight) *
        (num_lines_02 ? num_lines_02 : 1);

      int line_max_13 = static_cast<int>(vSize[1] * this->MaximumLineHeight) *
        (num_lines_13 ? num_lines_13 : 1);

      // Target size is to use 90% of x and y

      int tSize[2];
      tSize[0] = static_cast<int>(0.9*vSize[0]);
      tSize[1] = static_cast<int>(0.9*vSize[1]);

      // While the size is too small increase it

      while (height_02 < tSize[1] &&
             height_13 < tSize[1] &&
             max_width < tSize[0] &&
             height_02 < line_max_02 &&
             height_13 < line_max_13 &&
             fontSize < 100)
        {
        fontSize++;
        for (i = 0; i < 4; ++i)
          {
          this->TextMapper[i]->GetTextProperty()->SetFontSize(fontSize);
          this->TextMapper[i]->GetSize(viewport, tempi + i * 2);
          }
        height_02 = tempi[1] + tempi[5];
        height_13 = tempi[3] + tempi[7];
        width_01 = tempi[0] + tempi[2];
        width_23 = tempi[4] + tempi[6];
        max_width = (width_01 > width_23) ? width_01 : width_23;
        }

      // While the size is too large decrease it

      while ((height_02 > tSize[1] ||
              height_13 > tSize[1] ||
              max_width > tSize[0] ||
              height_02 > line_max_02 ||
              height_13 > line_max_13) &&
             fontSize > 0)
        {
        fontSize--;
        for (i = 0; i < 4; ++i)
          {
          this->TextMapper[i]->GetTextProperty()->SetFontSize(fontSize);
          this->TextMapper[i]->GetSize(viewport, tempi + i * 2);
          }
        height_02 = tempi[1] + tempi[5];
        height_13 = tempi[3] + tempi[7];
        width_01 = tempi[0] + tempi[2];
        width_23 = tempi[4] + tempi[6];
        max_width = (width_01 > width_23) ? width_01 : width_23;
        }

      fontSize = static_cast<int>(pow(static_cast<double>(fontSize),
              NonlinearFontScaleFactor)*LinearFontScaleFactor);
      if (fontSize > this->MaximumFontSize)
        {
        fontSize = this->MaximumFontSize;
        }
      this->FontSize = fontSize;
      for (i = 0; i < 4; ++i)
        {
        this->TextMapper[i]->GetTextProperty()->SetFontSize(fontSize);
        }

      // Now set the position of the TextActors

      this->SetTextActorsPosition(vSize);

      for (i = 0; i < 4; ++i)
        {
        this->TextActor[i]->SetProperty(this->GetProperty());
        }
      }
    this->BuildTime.Modified();
    this->LastImageSlice = ia;
    }

  // Everything is built, just have to render

  if (this->FontSize >= this->MinimumFontSize)
    {
    for (i = 0; i < 4; ++i)
      {
      this->TextActor[i]->RenderOpaqueGeometry(viewport);
      }
    }

  return 1;
}

/** Does this prop have some translucent polygonal geometry? */
// -+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-
int vtkCustomCornerAnnotation::HasTranslucentPolygonalGeometry()
{
  return 0;
}

// -+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-
void vtkCustomCornerAnnotation::SetTextActorsPosition(int vsize[2])
{
  this->TextActor[0]->SetPosition(5, 5);
  this->TextActor[1]->SetPosition(vsize[0] - 5, 5);
  this->TextActor[2]->SetPosition(5, vsize[1] - 5);
  this->TextActor[3]->SetPosition(vsize[0] - 5, vsize[1] - 5);
}

// -+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-
void vtkCustomCornerAnnotation::SetTextActorsJustification()
{
  vtkTextProperty* tprop = this->TextMapper[0]->GetTextProperty();
  tprop->SetJustificationToLeft();
  tprop->SetVerticalJustificationToBottom();

  tprop = this->TextMapper[1]->GetTextProperty();
  tprop->SetJustificationToRight();
  tprop->SetVerticalJustificationToBottom();

  tprop = this->TextMapper[2]->GetTextProperty();
  tprop->SetJustificationToLeft();
  tprop->SetVerticalJustificationToTop();

  tprop = this->TextMapper[3]->GetTextProperty();
  tprop->SetJustificationToRight();
  tprop->SetVerticalJustificationToTop();
}

// -+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-
void vtkCustomCornerAnnotation::SetText(int i, const char* text)
{
  if (0 > i || 3 < i)
    {
    return;
    }

  if (text == this->CornerText[i])
    {
    return;
    }
  this->CornerText[i] = text;
  this->Modified();
}

// -+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-
const char* vtkCustomCornerAnnotation::GetText(int i)
{
  if (0 > i || 3 < i)
    {
    return 0;
    }

  return this->CornerText[i].c_str();
}

// -+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-
void vtkCustomCornerAnnotation::ClearAllTexts()
{
  for (int i = 0; i < 4; ++i)
    {
    this->SetText(i, "");
    }
}

// -+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-
void vtkCustomCornerAnnotation::CopyAllTextsFrom(vtkCustomCornerAnnotation* ca)
{
  for (int i = 0; i < 4; ++i)
    {
    this->SetText(i, ca->GetText(i));
    }
}

// -+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-
void vtkCustomCornerAnnotation::PrintSelf(ostream& os, vtkIndent indent)
{
  this->Superclass::PrintSelf(os, indent);
  os << indent << "ImageSlice: " << this->GetImageSlice() << endl;
  os << indent << "MinimumFontSize: " << this->GetMinimumFontSize() << endl;
  os << indent << "MaximumFontSize: " << this->GetMaximumFontSize() << endl;
  os << indent << "LinearFontScaleFactor: " << this->GetLinearFontScaleFactor() << endl;
  os << indent << "NonlinearFontScaleFactor: " << this->GetNonlinearFontScaleFactor() << endl;
  os << indent << "WindowLevel: " << this->GetWindowLevel() << endl;
  os << indent << "Mapper: " << this->GetMapper() << endl;
  os << indent << "MaximumLineHeight: " << this->MaximumLineHeight << endl;
  os << indent << "LevelShift: " << this->LevelShift << endl;
  os << indent << "LevelScale: " << this->LevelScale << endl;
  os << indent << "TextProperty: " << this->TextProperty << endl;
  os << indent << "ShowSliceAndImage: " << this->ShowSliceAndImage << endl;
}
