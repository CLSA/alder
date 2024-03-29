/*=========================================================================

  Program:  Alder (CLSA Medical Image Quality Assessment Tool)
  Module:   QMainAlderWindow_p.h
  Language: C++

  Author: Dean Inglis <inglisd AT mcmaster DOT ca>

=========================================================================*/
#ifndef __QMainAlderWindow_p_h
#define __QMainAlderWindow_p_h

#include <QMainAlderWindow.h>
#include <ui_QMainAlderWindow.h>

// VTK includes
#include <vtkObject.h>
#include <vtkSmartPointer.h>

class vtkEventQtSlotConnect;

class QMainAlderWindowPrivate : public QObject, public Ui_QMainAlderWindow
{
  Q_OBJECT
  Q_DECLARE_PUBLIC(QMainAlderWindow);
  protected:
    QMainAlderWindow* const q_ptr;

  public:
    explicit QMainAlderWindowPrivate(QMainAlderWindow& object);
    virtual ~QMainAlderWindowPrivate();

    void setupUi(QMainWindow* window);
    void updateUi();

  public slots:
    // action event functions
    virtual void openInterview();
    virtual void showAtlas();
    virtual void showDicomTags();
    virtual void login();
    virtual void changePassword();
    virtual void loadUIDs();
    virtual void userManagement();
    virtual void updateInterviewDatabase();
    virtual void updateWaveDatabase();
    virtual void ratingCodes();
    virtual void reports();
    virtual void saveImage();
    virtual void updateAtlas(int id);
    virtual void updateDicom(int id);

    void abort();
    void showProgress(vtkObject*, unsigned long, void*, void* call_data);
    void hideProgress();
    void updateProgress(vtkObject*, unsigned long, void*, void* call_data);

    // help event functions
    virtual void about();
    virtual void manual();

    void changeActiveUserPassword(QString);

  private:
    vtkSmartPointer<vtkEventQtSlotConnect> qvtkConnection;
    bool atlasVisible;
    bool dicomTagsVisible;
    QString lastSavePath;
};

#endif
