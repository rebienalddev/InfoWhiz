import { GoogleGenAI } from "@google/genai";
import * as fs from 'fs';

const ai = new GoogleGenAI({ apiKey: "AIzaSyBMqLHAjRLoYZK1vmYqNAOfe4A8uf_Z3-8" });

async function main() {
    const contents = [
        { text: "Summarize this document" },
        {
            inlineData: {
                mimeType: 'application/pdf',
                data: Buffer.from(fs.readFileSync("10_Handout_2.pdf")).toString("base64")
            }
        }
    ];

    const response = await ai.models.generateContent({
        model: "gemini-2.5-flash",
        contents: contents
    });
    console.log(response.text);
}

main();