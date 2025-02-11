/*
+-----------------------------------------------------------------------------------------+
| ILIAS open source                                                                       |
+-----------------------------------------------------------------------------------------+
| Copyright (c) 1998-2001 ILIAS open source, University of Cologne                        |
|                                                                                         |
| This program is free software; you can redistribute it and/or                           |
| modify it under the terms of the GNU General Public License                             |
| as published by the Free Software Foundation; either version 2                          |
| of the License, or (at your option) any later version.                                  |
|                                                                                         |
| This program is distributed in the hope that it will be useful,                         |
| but WITHOUT ANY WARRANTY; without even the implied warranty of                          |
| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the                           |
| GNU General Public License for more details.                                            |
|                                                                                         |
| You should have received a copy of the GNU General Public License                       |
| along with this program; if not, write to the Free Software                             |
| Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.             |
+-----------------------------------------------------------------------------------------+
*/

package de.ilias.services.transformation;


import org.apache.fop.apps.*;
import org.apache.logging.log4j.LogManager;
import org.apache.logging.log4j.Logger;

import javax.xml.transform.*;
import javax.xml.transform.sax.SAXResult;
import javax.xml.transform.stream.StreamSource;
import java.io.ByteArrayInputStream;
import java.io.ByteArrayOutputStream;
import java.io.IOException;
import java.io.InputStream;
import java.net.URISyntaxException;
import java.nio.charset.StandardCharsets;
import java.util.List;
import java.io.File;
import java.nio.file.Files;
import java.nio.file.Path;
import org.xml.sax.SAXException;

public class FO2PDF {

    private static FO2PDF instance = null;

    private final Logger logger = LogManager.getLogger(this.getClass().getName());
    private String foString = null;
    private byte[] pdfByteArray = null;
    private FopFactory fopFactory = null;

    /**
     * Singleton constructor
     */
    public FO2PDF() {
        final String pathToConfigFile = "/de/ilias/config/fopConfig.xml";

        try {
            logger.info("Trying to read fopConfig from: {}", pathToConfigFile);

            logger.info("Extract resource as temporary file ..");

            File jarFile = new File(FO2PDF.class
                    .getProtectionDomain()
                    .getCodeSource()
                    .getLocation()
                    .toURI());
            File jarDir = jarFile.getParentFile();

            if (jarDir == null || !jarDir.isDirectory()) {
                throw new IllegalStateException("Cannot determine the directory of the running JAR file.");
            }

            logger.info("Determined JAR directory path: {}", jarDir.toPath());

            File tempConfigFile = new File(jarDir, "fopConfig.xml");

            if (!tempConfigFile.exists()) {
                try (InputStream inputStream = FO2PDF.class.getResourceAsStream(pathToConfigFile)) {
                    if (inputStream == null) {
                        throw new IllegalStateException("Resource stream is null");
                    }

                    Files.copy(inputStream, tempConfigFile.toPath(), java.nio.file.StandardCopyOption.REPLACE_EXISTING);
                }
            }

            Path configFilePath = tempConfigFile.toPath();

            logger.info("fopConfig Path: {}", configFilePath.toUri());
            fopFactory = FopFactory.newInstance(new File(configFilePath.toUri()));
        } catch (SAXException | IOException | URISyntaxException | NullPointerException ex) {
            logger.error("Cannot load fop configuration: {}", pathToConfigFile, ex);
        }
    }

    /**
     * Get FO2PDF instance
     */
    public static FO2PDF getInstance() {

        if (instance == null) {
            return instance = new FO2PDF();
        }
        return instance;
    }

    /**
     * clear fop uri cache
     */
    public void clearCache() {

        fopFactory.getImageManager().getCache().clearCache();
    }

    public void transform()
            throws TransformationException {

        try {

            logger.info("Starting fop transformation...");

            FOUserAgent foUserAgent = fopFactory.newFOUserAgent();
//            foUserAgent.setTargetResolution(300);
            ByteArrayOutputStream out = new ByteArrayOutputStream();

            Fop fop = fopFactory.newFop(MimeConstants.MIME_PDF, foUserAgent, out);

//          Setup JAXP using identity transformer
            TransformerFactory factory = TransformerFactory.newInstance();
            Transformer transformer = factory.newTransformer(); // identity transformer

            Source src = new StreamSource(getFoInputStream());
            Result res = new SAXResult(fop.getDefaultHandler());

            transformer.transform(src, res);

            FormattingResults foResults = fop.getResults();
            List pageSequences = foResults.getPageSequences();
            for (Object pageSequence : pageSequences) {
                PageSequenceResults pageSequenceResults = (PageSequenceResults) pageSequence;
                logger.debug(
                        "PageSequence {} generated {} pages.",
                        !String.valueOf(pageSequenceResults.getID()).isEmpty()
                                ? pageSequenceResults.getID()
                                : "<no id>", pageSequenceResults.getPageCount()
                );
            }
            logger.info("Generated {} pages in total.", foResults.getPageCount());

            this.setPdf(out.toByteArray());

        } catch (TransformerConfigurationException e) {
            logger.warn("Configuration exception: {}", String.valueOf(e));
            throw new TransformationException(e);
        } catch (TransformerException e) {
            logger.warn("Transformer exception: {}", String.valueOf(e));
            throw new TransformationException(e);
        } catch (FOPException e) {
            throw new TransformationException(e);
        }
    }


    /**
     * @return Returns the foString.
     */
    public String getFoString() {
        return foString;
    }


    /**
     * @param foString The foString to set.
     */
    public void setFoString(String foString) {
        this.foString = foString;
    }

    public byte[] getPdf() {
        return this.pdfByteArray;
    }

    public void setPdf(byte[] ba) {
        this.pdfByteArray = ba;
    }


    private InputStream getFoInputStream() {
        return new ByteArrayInputStream(getFoString().getBytes(StandardCharsets.UTF_8));
    }
}
