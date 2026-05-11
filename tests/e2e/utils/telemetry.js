const fs = require('fs/promises');
const path = require('path');

function createTelemetryCollector(page) {
  const state = {
    consoleErrors: [],
    consoleWarnings: [],
    requestFailures: [],
    httpErrors: [],
    requestCounter: {},
  };

  page.on('console', (msg) => {
    const entry = {
      type: msg.type(),
      text: msg.text(),
      location: msg.location(),
    };

    if (msg.type() === 'error') {
      state.consoleErrors.push(entry);
    } else if (msg.type() === 'warning') {
      state.consoleWarnings.push(entry);
    }
  });

  page.on('request', (request) => {
    const fingerprint = `${request.method()} ${request.url()}`;
    state.requestCounter[fingerprint] = (state.requestCounter[fingerprint] || 0) + 1;
  });

  page.on('requestfailed', (request) => {
    state.requestFailures.push({
      url: request.url(),
      method: request.method(),
      failureText: request.failure() ? request.failure().errorText : 'unknown',
    });
  });

  page.on('response', async (response) => {
    const status = response.status();
    if (status >= 400) {
      let body = '';
      try {
        body = await response.text();
      } catch (err) {
        body = 'unable to read body';
      }

      state.httpErrors.push({
        url: response.url(),
        method: response.request().method(),
        status,
        bodySnippet: body.slice(0, 500),
      });
    }
  });

  return state;
}

async function writeTelemetryArtifact(testInfo, telemetry) {
  const outDir = testInfo.outputDir;
  await fs.mkdir(outDir, { recursive: true });
  const outFile = path.join(outDir, 'telemetry.json');
  await fs.writeFile(outFile, JSON.stringify(telemetry, null, 2), 'utf8');
  await testInfo.attach('telemetry', {
    path: outFile,
    contentType: 'application/json',
  });
}

function hasCriticalFrontendIssues(telemetry) {
  return telemetry.consoleErrors.length > 0 || telemetry.requestFailures.length > 0 || telemetry.httpErrors.length > 0;
}

function findPotentialDuplicateRequests(telemetry, threshold = 3) {
  const duplicates = [];
  for (const [fingerprint, count] of Object.entries(telemetry.requestCounter)) {
    if (count >= threshold) {
      duplicates.push({ fingerprint, count });
    }
  }
  return duplicates;
}

module.exports = {
  createTelemetryCollector,
  writeTelemetryArtifact,
  hasCriticalFrontendIssues,
  findPotentialDuplicateRequests,
};
