import { startStimulusApp } from "@symfony/stimulus-bridge";
// Load Stimulus controllers from the controllers directory

const app = startStimulusApp(
  require.context(
    "@symfony/stimulus-bridge/lazy-controller-loader!./controllers",

    true,

    /\.[jt]sx?$/
  )
);

export default app;
