/*=========================================================================

  Module:    QAlderSliderWidget.h
  Program:   Alder (CLSA Medical Image Quality Assessment Tool)
  Language:  C++
  Author:    Patrick Emond <emondpd AT mcmaster DOT ca>
  Author:    Dean Inglis <inglisd AT mcmaster DOT ca>

  Library:   CTK

  Copyright (c) Kitware Inc.

  Licensed under the Apache License, Version 2.0 (the "License");
  you may not use this file except in compliance with the License.
  You may obtain a copy of the License at

      http://www.apache.org/licenses/LICENSE-2.0.txt

  Unless required by applicable law or agreed to in writing, software
  distributed under the License is distributed on an "AS IS" BASIS,
  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
  See the License for the specific language governing permissions and
  limitations under the License.

=========================================================================*/

/**
 * @class QAlderSliderWidget
 *
 * @author Patrick Emond <emondpd AT mcmaster DOT ca>
 * @author Dean Inglis <inglisd AT mcmaster DOT ca>
 *
 * @brief A wrapper around a QAlderDoubleSlider and a QDoubleSpinBox.
 *
 * QAlderSliderWidget is a wrapper around a QAlderDoubleSlider and a QDoubleSpinBox
 * where the slider value and the spinbox value are synchronized.
 *
 * This class was adapted from the CTK library and is required for use in
 * the QAlderFramePlayerWidget.
 *
 * @see QAlderDoubleSlider, QSpinBox, QAlderFramePlayerWidget
 */
#ifndef __QAlderSliderWidget_h
#define __QAlderSliderWidget_h

// Qt includes
#include <QSlider>

class QDoubleSpinBox;
class QAlderDoubleSlider;
class QAlderSliderWidgetPrivate;

class QAlderSliderWidget : public QWidget
{
  Q_OBJECT
  Q_PROPERTY(int decimals READ decimals WRITE setDecimals)
  Q_PROPERTY(double singleStep READ singleStep WRITE setSingleStep)
  Q_PROPERTY(double pageStep READ pageStep WRITE setPageStep)
  Q_PROPERTY(double minimum READ minimum WRITE setMinimum)
  Q_PROPERTY(double maximum READ maximum WRITE setMaximum)
  Q_PROPERTY(double value READ value WRITE setValue)
  Q_PROPERTY(QString prefix READ prefix WRITE setPrefix)
  Q_PROPERTY(QString suffix READ suffix WRITE setSuffix)
  Q_PROPERTY(double tickInterval READ tickInterval WRITE setTickInterval)
  Q_PROPERTY(bool autoSpinBoxWidth READ isAutoSpinBoxWidth
    WRITE setAutoSpinBoxWidth)
  Q_PROPERTY(Qt::Alignment spinBoxAlignment READ spinBoxAlignment
    WRITE setSpinBoxAlignment)
  Q_PROPERTY(bool tracking READ hasTracking WRITE setTracking)
  Q_PROPERTY(bool spinBoxVisible READ isSpinBoxVisible
    WRITE setSpinBoxVisible);

  public:
    /** Superclass typedef */
    typedef QWidget Superclass;

    /** Constructors */
    explicit QAlderSliderWidget(QWidget* parent = 0);
    virtual ~QAlderSliderWidget();

    //@{
    /**
     * This property holds the sliders and spinbox minimum value.
     * FIXME: Test following specs.
     * When setting this property, the maximum is adjusted if necessary
     * to ensure that the range remains valid.
     * Also the slider's current value is adjusted to be within the new range.
     */
    double minimum() const;
    void setMinimum(double minimum);
    //@}

    //@{
    /**
     * This property holds the sliders and spinbox minimum value.
     * FIXME: Test following specs.
     * When setting this property, the maximum is adjusted if necessary
     * to ensure that the range remains valid.
     * Also the slider's current value is adjusted to be within the new range.
     */
    double maximum() const;
    void setMaximum(double maximum);
    //@}

    /** Utility function that set the min/max in once */
    void setRange(double min, double max);

    /**
     * This property holds the slider and spinbox current value.
     * QAlderSliderWidget forces the value to be within the
     * legal range: minimum <= value <= maximum.
     */
    double value() const;

    //@{
    /**
     * This property holds the single step.
     * The smaller of two natural steps that the
     * slider provides and typically corresponds to the
     * user pressing an arrow key.
     */
    double singleStep() const;
    void setSingleStep(double step);
    //@}

    //@{
    /**
     * This property holds the page step.
     * The larger of two natural steps that an abstract slider provides and
     * typically corresponds to the user pressing PageUp or PageDown.
     */
    double pageStep() const;
    void setPageStep(double step);
    //@}

    //@{
    /**
     * This property holds the precision of the spin box, in decimals.
     * Sets how many decimals the spinbox will use for displaying and interpreting doubles.
     */
    int decimals() const;
    void setDecimals(int decimals);
    //@}

    //@{
    /**
     * This property holds the spin box's prefix.
     * The prefix is prepended to the start of the displayed value.
     * Typical use is to display a unit of measurement or a currency symbol
     */
    QString prefix() const;
    void setPrefix(const QString& prefix);
    //@}

    //@{
    /**
     * This property holds the spin box's suffix.
     * The suffix is appended to the end of the displayed value.
     * Typical use is to display a unit of measurement or a currency symbol
     */
    QString suffix() const;
    void setSuffix(const QString& suffix);
    //@}

    //@{
    /**
     * This property holds the interval between tickmarks.
     * This is a value interval, not a pixel interval.
     * If it is 0, the slider will choose between lineStep() and pageStep().
     * The default value is 0.
     */
    double tickInterval() const;
    void setTickInterval(double ti);
    //@}

    //@{
    /**
     * This property holds the alignment of the spin box.
     * Possible Values are Qt::AlignLeft, Qt::AlignRight, and Qt::AlignHCenter.
     * By default, the alignment is Qt::AlignLeft
     */
    void setSpinBoxAlignment(Qt::Alignment alignment);
    Qt::Alignment spinBoxAlignment() const;
    //@}

    //@{
    /**
     * This property holds whether slider tracking is enabled.
     * If tracking is enabled (the default), the widget emits the valueChanged()
     * signal while the slider or spinbox is being dragged. If tracking is
     * disabled, the widget emits the valueChanged() signal only when the user
     * releases the slider or spinbox.
     */
    void setTracking(bool enable);
    bool hasTracking() const;
    //@}

    //@{
    /**
     * Set/Get the auto spinbox width
     * When the autoSpinBoxWidth property is on, the width of the SpinBox is
     * set to the same width of the largest QSpinBox of its
     * QAlderSliderWidget siblings.
     */
    bool isAutoSpinBoxWidth() const;
    void setAutoSpinBoxWidth(bool autoWidth);
    //@}

    /**
     * The Spinbox visibility can be controlled using setSpinBoxVisible() and
     * isSpinBoxVisible().
     */
    bool isSpinBoxVisible() const;

    /**
     * Returns the spinbox synchronized with the slider. Be careful
     * with what you do with the spinbox as the slider might change
     * properties automatically.
     */
    QDoubleSpinBox* spinBox();

    /**
     * Returns the slider synchronized with the spinbox. Be careful
     * with what you do with the slider as the spinbox might change
     * properties automatically.
     */
    QAlderDoubleSlider* slider();

  public Q_SLOTS:
    /**
     * Reset the slider and spinbox to zero (value and position)
     */
    void reset();
    void setValue(double value);
    void setSpinBoxVisible(bool visible);

  Q_SIGNALS:
    /**
     * When tracking is on (default), valueChanged is emitted when the
     * user drags the slider.
     * If tracking is off, valueChanged() is emitted only when the user
     * releases the mouse.
     * @see valueIsChanging QAbstractSlider::valueChanged
     */
    void valueChanged(double value);

    /**
     * valueIsChanging() is emitted whenever the slider is dragged and tracking
     * is turned off. You might want to use valueChanged instead.
     * It behaves the same way than QAbstractSlider::sliderMoved()
     * @see valueChanged QAbstractSlider::sliderMoved
     */
    void valueIsChanging(double value);

  protected Q_SLOTS:

    void startChanging();
    void stopChanging();
    void changeValue(double value);

  protected:
    virtual bool eventFilter(QObject* obj, QEvent* event);

  protected:
    QScopedPointer<QAlderSliderWidgetPrivate> d_ptr;

  private:
    Q_DECLARE_PRIVATE(QAlderSliderWidget);
    Q_DISABLE_COPY(QAlderSliderWidget);
};

#endif
