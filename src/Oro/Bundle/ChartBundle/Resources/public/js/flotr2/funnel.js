define([
    'jquery',
    'underscore',
    'flotr2'
], function($, _, Flotr) {
    'use strict';

    Flotr.addType('funnel', {
        options: {
            show: false,
            lineWidth: 2,
            fill: true,
            fillColor: null,
            fillOpacity: 0.5,
            fontColor: '#B2B2B2',
            explode: 5,
            marginX: 250,
            marginY: 20,
            colors: ['#B5D8DA', '#E6CFE3', '#CEDFF6', '#EFD7A7', '#CAB0B0', '#A2B4D2',
                '#BDD4B3', '#8985C2', '#ECB574', '#84A377']
        },
        allSeries: [],
        shapes: [],
        stacked: false,

        draw: function(options) {
            const shape = this.calculateShape(options);
            this.shapes.push(shape);

            this.plot(shape, options);
            if (options.showLabels) {
                this.renderLabel(shape, options);
            }
        },

        hit: function(options) {
            let s1;
            let s2;
            let s3;
            const args = options.args;
            const mouse = args[0];
            const x = mouse.relX;
            const y = mouse.relY;
            const fullRectangle = !_.every(this.shapes, function(seg, i) {
                let belongSide = true;
                /**
                 *  left/right rectangle side case
                 *  detect mouse is in figure
                 *
                 *  (x1 - x0) * (y2 - y1) - (x2 - x1) * (y1 - y0)
                 *  (x2 - x0) * (y3 - y2) - (x3 - x2) * (y2 - y0)
                 *  (x3 - x0) * (y1 - y3) - (x1 - x3) * (y3 - y0)
                 */

                if (x >= seg.x1 && x <= seg.x4 && y >= seg.y1 && y <= seg.y4) {
                    s1 = (seg.x1 - x) * (seg.y4 - seg.y1) - (seg.x1 - seg.x1) * (seg.y1 - y);
                    s2 = (seg.x1 - x) * (seg.y4 - seg.y4) - (seg.x4 - seg.x1) * (seg.y4 - y);
                    s3 = (seg.x4 - x) * (seg.y1 - seg.y4) - (seg.x1 - seg.x4) * (seg.y4 - y);
                    if (s1 === 0 || s2 === 0 || s3 === 0 ||
                        (s1 > 0 && s2 > 0 && s3 > 0) || (s1 < 0 && s2 < 0 && s3 < 0)) {
                        belongSide = false;
                    }
                }

                // right rectangle side case
                if (x >= seg.x3 && x <= seg.x2 && y >= seg.y2 && y <= seg.y3) {
                    s1 = (seg.x3 - x) * (seg.y2 - seg.y3) - (seg.x2 - seg.x3) * (seg.y3 - y);
                    s2 = (seg.x2 - x) * (seg.y3 - seg.y2) - (seg.x2 - seg.x2) * (seg.y2 - y);
                    s3 = (seg.x2 - x) * (seg.y3 - seg.y3) - (seg.x3 - seg.x2) * (seg.y3 - y);
                    if (s1 === 0 || s2 === 0 || s3 === 0 ||
                        (s1 > 0 && s2 > 0 && s3 > 0) || (s1 < 0 && s2 < 0 && s3 < 0)) {
                        belongSide = false;
                    }
                }

                // full rectangle case
                if (y >= seg.y1 && y <= seg.y3 && x >= seg.x1 && x <= seg.x2 && belongSide !== false) {
                    if (this.stacked === i) {
                        return false;
                    }

                    this.stacked = i;
                    this.clearHit(options);
                    this.drawHit(options, i);

                    return false;
                }

                return true;
            }, this);

            if (fullRectangle) {
                return;
            }

            this.stacked = false;
            this.clearHit(options);
        },

        drawHit: function(options, i) {
            const context = options.context;
            const shape = this.shapes[i];
            context.save();
            context.lineJoin = 'round';
            context.lineWidth = options.lineWidth;
            context.strokeStyle = '#FF3F19';
            context.beginPath();
            context.moveTo(shape.x1 + options.lineWidth * 2, shape.y1);
            context.lineTo(shape.x2 + options.lineWidth * 2, shape.y2);
            context.lineTo(shape.x3 + options.lineWidth * 2, shape.y3);
            context.lineTo(shape.x4 + options.lineWidth * 2, shape.y4);
            context.closePath();
            context.stroke();
            context.restore();
        },

        clearHit: function(options) {
            const context = options.context;
            context.save();
            _.each(this.shapes, function(shape) {
                context.clearRect(
                    shape.x1 - options.lineWidth * 2,
                    shape.y1 - options.lineWidth,
                    shape.x2 - shape.x1 + options.lineWidth * 5,
                    shape.y4 - shape.y1 + options.lineWidth * 5
                );
            });
            context.restore();
        },

        /**
         *
         * @param shape
         * @param options
         */
        plot: function(shape, options) {
            const context = options.context;
            context.save();
            context.lineJoin = 'round';
            context.lineWidth = options.lineWidth;
            context.strokeStyle = shape.color;
            context.fillStyle = Flotr.Color.parse(shape.color).alpha(options.fillOpacity).toString();
            context.translate(0.5, 0.5);
            context.beginPath();
            context.moveTo(shape.x1, shape.y1);
            context.lineTo(shape.x2, shape.y2);
            context.lineTo(shape.x3, shape.y3);
            context.lineTo(shape.x4, shape.y4);
            context.closePath();
            context.stroke();
            context.fill();
            context.restore();
        },

        /**
         *
         * @param options
         * @returns {Object}
         */
        calculateShape: function(options) {
            let leftHeight;
            let width;
            const index = options.index;
            const series = this.allSeries[index];
            const shift = options.explode || 0;
            const shiftX = options.marginX || 0;
            const shiftY = options.marginY || 0;
            const frameWidth = options.width - shiftX;
            const frameHeight = options.height - shiftY * 2 - shift;

            leftHeight = index > 0 ? this.shapes[index - 1].leftHeight : frameHeight + (options.extraHeight || 0);
            width = index > 0 ? this.shapes[index - 1].bottomWidth : frameWidth;
            if (shift > 0 && index !== 0) {
                if (!series.isNozzle) {
                    width = Math.ceil(width * (leftHeight - shift) / leftHeight);
                }
                leftHeight -= shift;
            }

            const shape = {};
            shape.color = series.color = options.colors[index];
            shape.height = Math.round(frameHeight / this.total() * options.data[0]);
            if (shift > 0 && shape.height > shift) {
                shape.height -= shift;
            }

            /**
             * Each segment calculate
             *
             *    D    D0            A                 D1
             *     ------------------------------------  AB  - funnel segment size (height)
             *     \   |            |                /         OR height of previous segment -> "prevStepHeight"
             *      \  |            |               /    AP  - full height (with margins) -> "marginHeight"
             *       \ |            |              /     DD1 - full width
             *        \|            |             /            (OR full width of previous segment) "prevStepWidth"
             *         \            | B          /       DD0 - "prevStepWidthDelta"
             *        C -------------------------        On any calculation step we should know BC
             *           \          |          /         to correctly render segment of funnel
             *            \         |         /
             *             \        |        /
             *              \       |       /
             *               \      |      /
             *                \     |     /
             *                 \    |    /
             *                  \   |   /
             *                   \  |  /
             *                    \ | /
             *                     \|/
             *                      | P
             */

            const AD = Math.ceil(width / 2);
            const BC = Math.ceil(AD * (leftHeight - shape.height) / leftHeight);

            shape.leftHeight = leftHeight - shape.height;
            shape.topWidth = AD * 2;
            shape.bottomWidth = (series.isNozzle ? AD : BC) * 2;

            shape.x1 = Math.ceil((frameWidth - width) / 2);
            shape.x2 = shape.x1 + shape.topWidth;
            shape.x3 = shape.x1 + (shape.topWidth + shape.bottomWidth) / 2;
            shape.x4 = shape.x1 + (shape.topWidth - shape.bottomWidth) / 2;
            shape.y1 = shape.y2 = frameHeight - leftHeight + shiftY;
            shape.y3 = shape.y4 = frameHeight - shape.leftHeight + shiftY;

            return shape;
        },

        /**
         *
         * @param shape
         * @param options
         */
        renderLabel: function(shape, options) {
            const context = options.context;
            const index = options.index;
            const series = this.allSeries[index];
            const $prev = $(options.element).find('.flotr-grid-label').last();
            const distX = options.width - options.marginX * 0.8;
            const distY = !$prev[0] ? options.marginY
                : $prev.position().top + $prev.outerHeight(true) + options.fontSize * 1.2;
            const style = {
                size: options.fontSize * 1.2,
                color: options.fontColor,
                weight: 1.5
            };
            options.htmlText = true;
            style.textAlign = 'left';
            style.textBaseline = 'top';
            style.wordWrap = 'break-word';

            const html = [];
            const divStyle =
                    style.textBaseline + ':' + (distY - style.size) + 'px;' +
                    style.textAlign + ':' + (distX + 10) + 'px;';

            html.push('<div style="', divStyle, '" class="flotr-grid-label funnel-label">', series.label, '</div>');

            const div = Flotr.DOM.node('<div style="color:#454545" class="flotr-labels"></div>');
            Flotr.DOM.insert(div, html.join(''));
            Flotr.DOM.insert(options.element, div);

            // label line
            context.beginPath();
            context.lineWidth = 1;
            context.strokeStyle = options.fontColor;
            context.moveTo(shape.x2, shape.y2);
            context.lineTo(distX, distY);
            context.stroke();
        },

        /**
         * Prepares data for rendering
         *  - normalize values
         *  - caches data series
         *  - calculates sum all not nozzle items
         *
         * @param series
         * @param data
         * @param options
         */
        extendRange: function(series, data, options) {
            if (data[0] <= 0) {
                // normalize min value
                data[0] = 0.0001;
            }

            // collect all data series
            this.allSeries.push(series);
            // save index of related series
            options.index = this.allSeries.length - 1;

            if (!series.isNozzle) {
                // calculate sum all not nozzle values
                this._sum = (this._sum || 0) + data[0];
            }
        },

        /**
         * Calculates total
         *  - caches value in _total property
         *
         * @returns {number}
         */
        total: function() {
            const sum = this._sum;
            let total = 0;

            if (!this._total) {
                _.each(this.allSeries, function(series) {
                    if (series.isNozzle) {
                        // nozzle is always === 10% of sum
                        series.data[0] = sum * 0.1;
                    }
                    total += series.data[0];
                });
                this._total = Math.round(total);
            }

            return this._total;
        }
    });
});
