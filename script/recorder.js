/*License (MIT)

Copyright © 2013 Matt Diamond

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated 
documentation files (the "Software"), to deal in the Software without restriction, including without limitation 
the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and 
to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of 
the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO 
THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE 
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF 
CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER 
DEALINGS IN THE SOFTWARE.
*/

(function (window) {

    var Recorder = function (mediaStreamSource) {
        var bufferSize = 4096;
        var websocket;
        this.context = mediaStreamSource.context;
        if (!this.context.createScriptProcessor) {
            this.node = this.context.createJavaScriptNode(bufferSize, 2, 2);
        } else {
            this.node = this.context.createScriptProcessor(bufferSize, 2, 2);
        }

        mediaStreamSource.connect(this.node);
        this.node.connect(this.context.destination);

        var recording = false;

        this.node.onaudioprocess = function (event) {
            if (recording) {
                var inputData = event.inputBuffer.getChannelData(0);
                var inputLength = Math.floor(inputData.length / 3);
                var resultArray = new Float32Array(inputLength);

                var index = 0;
                var inputIndex = 0;

                while (index < inputLength) {
                    resultArray[index++] = inputData[inputIndex];
                    inputIndex += 3;
                }

                var offset = 0;
                var buffer = new ArrayBuffer(inputLength * 2);
                var view = new DataView(buffer);
                for (var i = 0; i < resultArray.length; i++, offset += 2) {
                    var s = Math.max(-1, Math.min(1, resultArray[i]));
                    view.setInt16(offset, s < 0 ? s * 0x8000 : s * 0x7FFF, true);
                }

                websocket.send(view);
            }
        }

        this.record = function (ws) {
            websocket = ws;
            this.sendHeader();
            recording = true;
        }

        this.stop = function () {
            recording = false;
            this.node.disconnect(0);
        }

        this.sendHeader = function () {
            var sampleLength = 3000000;
            var mono = true;
            var sampleRate = 16000;
            var buffer = new ArrayBuffer(44);
            var view = new DataView(buffer);

            /* RIFF identifier */
            writeString(view, 0, 'RIFF');
            /* file length */
            view.setUint32(4, 32 + sampleLength * 2, true);
            /* RIFF type */
            writeString(view, 8, 'WAVE');
            /* format chunk identifier */
            writeString(view, 12, 'fmt ');
            /* format chunk length */
            view.setUint32(16, 16, true);
            /* sample format (raw) */
            view.setUint16(20, 1, true);
            /* channel count */
            view.setUint16(22, mono ? 1 : 2, true);
            /* sample rate */
            view.setUint32(24, sampleRate, true);
            /* byte rate (sample rate * block align) */
            view.setUint32(28, sampleRate * 2, true);
            /* block align (channel count * bytes per sample) */
            view.setUint16(32, 2, true);
            /* bits per sample */
            view.setUint16(34, 16, true);
            /* data chunk identifier */
            writeString(view, 36, 'data');
            /* data chunk length */
            view.setUint32(40, sampleLength * 2, true);

            websocket.send(view);
        }
    };

    window.Recorder = Recorder;

})(window);

function writeString(view, offset, string) {
    for (var i = 0; i < string.length; i++) {
        view.setUint8(offset + i, string.charCodeAt(i));
    }
}
