/*=========================================================================

  Program:  Alder (CLSA Medical Image Quality Assessment Tool)
  Module:   QAlderImageWidget.cxx
  Language: C++

  Author: Dean Inglis <inglisd AT mcmaster DOT ca>

=========================================================================*/
#include <QAlderImageWidget.h>
#include <ui_QAlderImageWidget.h>

// Alder includes
#include <QAlderSliceView.h>

// VTK includes
#include <vtkEventForwarderCommand.h>
#include <vtkEventQtSlotConnect.h>
#include <vtkImageData.h>
#include <vtkRenderer.h>
#include <vtkRenderWindow.h>

// C++ includes
#include <sstream>
#include <stdexcept>

class QAlderImageWidgetPrivate : public Ui_QAlderImageWidget
{
  Q_DECLARE_PUBLIC(QAlderImageWidget);
  protected:
    QAlderImageWidget* const q_ptr;

  public:
    explicit QAlderImageWidgetPrivate(QAlderImageWidget& object);
    virtual ~QAlderImageWidgetPrivate();

    virtual void setupUi(QWidget* widget);
};

// -+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-
//
// QAlderImageWidgetPrivate methods
//
// -+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-
QAlderImageWidgetPrivate::QAlderImageWidgetPrivate(
  QAlderImageWidget& object)
  : q_ptr(&object)
{
}

// -+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-
QAlderImageWidgetPrivate::~QAlderImageWidgetPrivate()
{
}

// -+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-
void QAlderImageWidgetPrivate::setupUi(QWidget* widget)
{
  Q_Q(QAlderImageWidget);

  this->Ui_QAlderImageWidget::setupUi(widget);
  this->imageControl->setSliceView(this->sliceView);
  this->framePlayerWidget->setSliceView(this->sliceView);
}

// -+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-
QAlderImageWidget::QAlderImageWidget(QWidget* parent)
  : Superclass(parent)
  , d_ptr(new QAlderImageWidgetPrivate(*this))
{
  Q_D(QAlderImageWidget);
  d->setupUi(this);
  this->reset();
}

// -+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-
QAlderImageWidget::~QAlderImageWidget()
{
}

// -+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-
bool QAlderImageWidget::eventFilter(QObject *obj, QEvent *event)
{
  Q_D(QAlderImageWidget);
  if (obj == d->sliceView->VTKWidget())
  {
    if (QEvent::Enter == event->type())
    {
      d->frame->setStyleSheet("border : 3px solid red");
    }
    if (QEvent::Leave == event->type())
    {
      d->frame->setStyleSheet("border : 3px solid green");
    }
  }
  return false;
}

// -+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-
void QAlderImageWidget::reset()
{
  Q_D(QAlderImageWidget);
  d->sliceView->setImageToSinusoid();
}

// -+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-
void QAlderImageWidget::load(
  const QString& fileName, vtkEventForwarderCommand* forward)
{
  Q_D(QAlderImageWidget);
  if (!d->sliceView->load(fileName, forward))
  {
    std::stringstream stream;
    stream << "Unable to load image file \"" << fileName.toStdString() << "\"";
    throw std::runtime_error(stream.str());
  }
}

// -+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-
void QAlderImageWidget::save(const QString& fileName)
{
  Q_D(QAlderImageWidget);
  d->sliceView->writeSlice(fileName);
}

// -+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-+#+-
vtkImageData* QAlderImageWidget::imageData()
{
  Q_D(QAlderImageWidget);
  return d->sliceView->imageData();
}
