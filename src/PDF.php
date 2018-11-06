<?php namespace Hpolthof\LaravelHelpers;

use Illuminate\Http\Response;

class PDF
{
    /**
     * @param string $data
     * @param bool $inline
     * @param string $filename
     * @return \Illuminate\Contracts\Routing\ResponseFactory|Response
     */
    public static function response($data, $inline = true, $filename = 'document.pdf')
    {
        return response($data, 200, [
            'Content-type' => 'application/pdf',
            'Content-size' => strlen($data),
            'Content-disposition' => ($inline ? 'inline' : 'attachment') . '; filename="'.urlencode($filename).'"',
        ]);
    }

    public static function compressPdf($data)
    {
        try {
            // Store as tempfile
            $fileIn = tempnam(sys_get_temp_dir(), 'pdf-compress-');
            $fileOut = tempnam(sys_get_temp_dir(), 'pdf-compress-');
            file_put_contents($fileIn, $data);

            // Compress
            exec("ghostscript -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/ebook -dMaxInlineImageSize=0 -dNOPAUSE -dQUIET -dBATCH -sOutputFile={$fileOut} {$fileIn}");

            // Remove temp
            unlink($fileIn);

            // Return result
            if (file_exists($fileOut)) {
                $result = file_get_contents($fileOut);
                unlink($fileOut);
                return $result;
            }
        } catch (\Exception $exception) {}

        // If something went wrong, forget compression!
        return $data;
    }

    /**
     * Convert a PDF to a non editable monochrome PDF.
     * This function is intended for sending a PDF as Fax.
     *
     * @param $data
     * @return string
     */
    public static function monochrome($data)
    {
        try {
            // Store as tempfile
            $fileIn = tempnam(sys_get_temp_dir(), 'pdf-compress-').'.pdf';
            $fileOut = tempnam(sys_get_temp_dir(), 'pdf-compress-');
            file_put_contents($fileIn, $data);

            exec("/usr/bin/pdftoppm -png -aa yes $fileIn $fileOut");
            $files = glob($fileOut.'-*.png');
            exec("convert -colorspace gray +dither -colors 2 -type bilevel ".implode(' ', $files).' '.$fileOut.'.pdf');

            $result = file_get_contents($fileOut.'.pdf');

            // Remove temp
            unlink($fileIn);
            unlink($fileOut.'.pdf');
            foreach($files as $file) {
                unlink($file);
            }

        } catch (\Exception $exception) {}

        return $result;
    }

    public static function flatten($data)
    {
        try {
            // Store as tempfile
            $fileIn = tempnam(sys_get_temp_dir(), 'pdf-compress-').'.pdf';
            $fileOut = tempnam(sys_get_temp_dir(), 'pdf-compress-');
            file_put_contents($fileIn, $data);

            exec("/usr/bin/pdftoppm -png -aa yes $fileIn $fileOut");
            $files = glob($fileOut.'-*.png');
            exec("convert -quality 100 ".implode(' ', $files).' '.$fileOut.'.pdf');

            $result = file_get_contents($fileOut.'.pdf');

            // Remove temp
            unlink($fileIn);
            unlink($fileOut.'.pdf');
            foreach($files as $file) {
                unlink($file);
            }

        } catch (\Exception $exception) {}

        return $result;
    }

    public static function metadata($data, $title = '', $author = '', $subject = '', $keywords = '', $creator = '', $producer = '')
    {
        $date = date('YMDHis');
        $metadata = <<<EOF
[ /Title ($title)
  /Author ($author)
  /Subject ($subject)
  /Keywords ($keywords)
  /ModDate (D:$date)
  /CreationDate (D:$date)
  /Creator ($creator)
  /Producer ($producer)
  /DOCINFO pdfmark
EOF;

        try {
            // Store as tempfile
            $fileIn = tempnam(sys_get_temp_dir(), 'pdf-compress-').'.pdf';
            $fileOut = tempnam(sys_get_temp_dir(), 'pdf-compress-').'.pdf';
            file_put_contents($fileIn, $data);
            file_put_contents($fileIn.'.pdfmarks', $metadata);

            exec("gs -q -dBATCH -dNOPAUSE -sDEVICE=pdfwrite -sOutputFile=$fileOut $fileIn $fileIn.pdfmarks");

            $result = file_get_contents($fileOut);

            // Remove temp
            unlink($fileIn);
            unlink($fileIn.'.pdfmarks');
            unlink($fileOut);
        } catch (\Exception $exception) {}

        return $result;
    }

    /**
     * @param $data
     * @param null $filename
     * @return Response
     */
    public static function toResponse($data, $filename = null)
    {
        if ($filename === null) {
            $disposition = "inline; filename=document.pdf";
        } else {
            $file = urlencode($filename);
            $disposition = "attachment; filename={$file}";
        }

        return new Response($data, 200, [
            'Content-type' => 'application/pdf',
            'Content-size' => strlen($data),
            'Content-disposition' => $disposition,
        ]);
    }
}